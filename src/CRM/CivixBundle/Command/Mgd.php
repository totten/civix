<?php

namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Utils\Files;

class Mgd {

  public static function assertManageableEntity(string $entityName, $id, string $extKey, string $managedName, string $managedFileName): void {
    $io = \Civix::io();
    $existingMgd = \civicrm_api4('Managed', 'get', [
      'select' => ['module', 'name', 'id'],
      'where' => [
        ['entity_type', '=', $entityName],
        ['entity_id', '=', $id],
      ],
      'checkPermissions' => FALSE,
    ])->first();
    if ($existingMgd) {
      if ($existingMgd['module'] !== $extKey || $existingMgd['name'] !== $managedName) {
        $io->warning([
          sprintf("Requested entity (%s) is already managed by \"%s\" (#%s). Adding new entity \"%s\" would create conflict.",
            "$entityName $id",
            $existingMgd['module'] . ':' . $existingMgd['name'],
            $existingMgd['id'],
            "$extKey:$managedName"
          ),
        ]);
      }
      if (!file_exists($managedFileName)) {
        $io->warning([
          sprintf('The managed entity (%s) already exists in the database, but the expected file (%s) does not exist.',
            "$extKey:$managedName",
            Files::relativize($managedFileName, \CRM\CivixBundle\Application::findExtDir())
          ),
          'The new file will be created, but you may have a conflict within this extension.',
        ]);
      }
    }
  }

  /**
   * Check if the record name contains the extension short name
   * (case-insensitive matching with or without underscores or hyphens).
   */
  public static function nameContainsShortName(string $name, string $shortName): bool {
    $cleanShort = strtolower(str_replace(['_', '-'], '', $shortName));
    if ($cleanShort === '') {
      return FALSE;
    }
    $cleanRecordName = strtolower(str_replace(['_', '-'], '', $name));
    return strpos($cleanRecordName, $cleanShort) !== FALSE;
  }

  /**
   * If the record has a unique index on name or name+domain_id,
   * ensure the name contains the extension short name.
   */
  public static function ensureUniqueName(string $entityName, $id, string $shortName): void {
    if (!self::hasUniqueNameIndex($entityName) || empty($shortName)) {
      return;
    }

    try {
      $record = \civicrm_api4($entityName, 'get', [
        'checkPermissions' => FALSE,
        'where' => [['id', '=', $id]],
        'select' => ['id', 'name'],
      ])->first();
    }
    catch (\Exception $e) {
      return;
    }

    if (!$record || !isset($record['name'])) {
      return;
    }

    $name = (string) $record['name'];
    if (self::nameContainsShortName($name, $shortName)) {
      return;
    }

    $daoClass = \CRM_Core_DAO_AllCoreTables::getDAONameForEntity($entityName);
    $fields = ($daoClass && is_callable([$daoClass, 'fields'])) ? $daoClass::fields() : [];
    $maxlength = (int) ($fields['name']['maxlength'] ?? 64);
    $maxlength = $maxlength > 0 ? $maxlength : 64;

    $newName = self::formatUniqueName($name, $shortName, $entityName, $maxlength);

    if ($newName === $name) {
      return;
    }

    $io = \Civix::io();
    $io->note("To prevent name conflicts (with other extensions or user-created items), it's a good idea to include this extension in the name of the $entityName.");
    if ($io->confirm(sprintf('Rename %s to "%s" before exporting?', $entityName, $newName), TRUE)) {
      \civicrm_api4($entityName, 'update', [
        'checkPermissions' => FALSE,
        'where' => [['id', '=', $id]],
        'values' => ['name' => $newName],
      ]);
    }
  }

  /**
   * If exporting an Afform, ensure the name contains the extension short name.
   */
  public static function ensureUniqueAfformName(array &$afform, string $shortName): void {
    if (empty($shortName) || self::nameContainsShortName($afform['name'], $shortName)) {
      return;
    }

    $newName = self::formatUniqueName($afform['name'], $shortName, 'Afform', 255);
    if ($newName === $afform['name']) {
      return;
    }

    $io = \Civix::io();
    $io->note("To prevent name conflicts (with other extensions or user-created forms), it's a good idea to include this extension in the name of the Afform.");
    if ($io->confirm(sprintf('Rename exported Afform to "%s"?', $newName), TRUE)) {
      $afform['name'] = $newName;
    }
  }

  /**
   * Format a unique name for an entity, prepending the extension short name.
   * For Afforms starting with 'afform' or 'afsearch', the extension short name is placed after that prefix.
   */
  public static function formatUniqueName(string $name, string $shortName, ?string $entityName = NULL, int $maxlength = 64): string {
    if ($entityName === 'Afform' && preg_match('/^(afform|afsearch)[-_]?(.*)$/i', $name, $matches)) {
      $prefix = $matches[1];
      $rest = $matches[2];
      $targetName = ($rest !== '') ? "{$prefix}_{$shortName}_{$rest}" : "{$prefix}_{$shortName}";
    }
    else {
      $targetName = ($name !== '') ? "{$shortName}_{$name}" : $shortName;
    }

    if (class_exists('CRM_Utils_String')) {
      return \CRM_Utils_String::munge($targetName, '_', $maxlength);
    }
    return substr(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($targetName)), 0, $maxlength);
  }

  /**
   * Check if an entity has a unique index on name or name+domain_id
   */
  public static function hasUniqueNameIndex(string $entityName): bool {
    try {
      $indices = \Civi::entity($entityName)->getMeta('indices') ?? [];
    }
    catch (\Throwable $t) {
      return FALSE;
    }
    foreach ($indices as $index) {
      if (!empty($index['unique'])) {
        $fields = array_keys($index['fields'] ?? []);
        sort($fields);
        if ($fields === ['name'] || $fields === ['domain_id', 'name']) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
