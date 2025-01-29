<?php

use CRM\CivixBundle\Utils\Formatting;
use CRM\CivixBundle\Utils\Naming;

return function (\CRM\CivixBundle\Generator $gen) {

  $sqlFiles = Civix::extDir()->search('glob:sql/auto_*.sql');
  $relSqlFiles = array_map([\CRM\CivixBundle\Utils\Files::class, 'relativize'], $sqlFiles);

  $oldClass = (string) $gen->infoXml->get()->upgrader;
  $newClass = sprintf('CiviMix\\Schema\\%s\\AutomaticUpgrader', Naming::createCamelName($gen->infoXml->getFile()));
  if (strpos($oldClass, 'CiviMix\\Schema\\') === 0) {
    $newClass = $oldClass;
  }
  $delegateClass = Naming::createClassName($gen->infoXml->getNamespace(), 'Upgrader');

  $steps = [];
  $xmlSchemaFiles = civix::extDir()->search('find:xml/schema/*.xml');
  if (!empty($xmlSchemaFiles)) {
    $steps[] = "Convert xml/schema/*.xml to schema/*.entityType.php";
    $steps[] = "Regenerate CRM/*/DAO/*.php";
    $steps[] = "Update mixin entity-types-php@1 to entity-types-php@2";
  }

  if (!empty($oldClass) && strpos($oldClass, 'CiviMix\\Schema\\') !== 0) {
    $steps[] = $oldClass !== $newClass ? "Update info.xml to use $newClass (which delegates to $delegateClass)" : "Update info.xml to use $newClass";
  }
  if (!empty($relSqlFiles)) {
    $steps[] = 'Delete ' . implode(' and ', $relSqlFiles);
  }

  if (empty($steps)) {
    return;
  }

  $warnings = [
    "Your target environment is CiviCRM v5.44 or earlier.",
    "The SQL files include custom/manual statements.",
  ];
  if ($oldClass) {
    // $warnings[] = "$delegateClass class has non-standard revision tracking (such as Step-NNN class-files).";
    $warnings[] = "The class $delegateClass overrides any internal plumbing (e.g. setCurrentRevision(), appendTask(), or getRevisions())";
  }
  if ($oldClass && $oldClass !== $delegateClass) {
    $warnings[] = "The old upgrader ($oldClass) does not match the expected name ($delegateClass).";
  }

  $rows = [
    "This update converts data-storage from Entity Framework v1 (EFv1) to Entity Framework v2 (EFv2).",
    "EFv2 stores schema as *.php. It has simpler workflows and less boilerplate. SQL is generated during installation. (More details: https://github.com/totten/civix/wiki/Entity-Templates)",
    // "For a full comparison, see: https://github.com/totten/civix/wiki/Entity-Templates",
    "The upgrader will make the following changes:\n\n" . Formatting::ol("%s\n", $steps),
    "This should work for many extensions, but it should be tested. You may encounter issues if any of these scenarios apply:\n\n" . Formatting::ol("%s\n", $warnings),
    "You may skip this update. However, going forward, civix will only support EFv2. You will be responsible for maintaining any boilerplate for EFv1.",
  ];

  Civix::io()->title("Entity Framework v1 => v2");
  Civix::io()->note($rows);

  $actions = [
    'y' => 'Yes, update to Entity Framework v2',
    'n' => 'No, stay on Entity Framework v1',
    'a' => 'Abort',
  ];
  $action = Civix::io()->choice("Should we apply the update?", $actions, 'y');
  if ($action === 'a') {
    throw new \RuntimeException('User stopped upgrade');
  }
  if ($action === 'n') {
    return;
  }

  // Build a table to describe the EFv1 files (*.xml, *.entityType.php).
  $trimFiles = function($array, $trimSuffix) use ($gen) {
    $array = array_map(function($file) use ($trimSuffix, $gen) {
      $f = \CRM\CivixBundle\Utils\Files::relativize($file, $gen->baseDir->string() . '/');
      return substr($f, 0, -1 * strlen($trimSuffix));
    }, $array);
    sort($array);
    return $array;
  };
  $xmls = $trimFiles($gen->baseDir->search('find:xml/schema/*.xml'), '.xml');
  $phps = $trimFiles($gen->baseDir->search('find:xml/schema/*.entityType.php'), '.entityType.php');
  $all = array_unique(array_merge($phps, $xmls));
  $headers = ['Entity', 'Folder', 'XML', 'PHP'];
  $rows = [];
  foreach ($all as $filePrefix) {
    $rows[] = [
      basename($filePrefix),
      "./" . dirname($filePrefix) . '/',
      in_array($filePrefix, $xmls) ? 'Found' : 'Missing',
      in_array($filePrefix, $phps) ? 'Found' : 'Missing',
    ];
  }

  Civix::io()->section('Review Entity Framework v1');

  $notes = [
    "Before converting, please review the existing entity configuration files.",
    'Every entity should normally have a pair of files (XML+PHP).',
    // 'In EFv1, every entity in ./xml/schema/ should have two files (*.xml and *.entityType.php). We have detected the following:',
    rtrim(Formatting::table($headers, $rows), "\n"),
    'The converter will read any XML files and generate new (consolidated) PHP files in ./schema.',
  ];

  Civix::io()->note($notes);
  if ($phps != $xmls) {
    Civix::io()->caution([
      "EFv1 has some inconsistencies. The may indicate inactive files or custom registration rules.",
      "We may still continue conventing files for EFv2.",
      "After the upgrade, you should inspect the entity list carefully.",
    ]);
  }

  if (!Civix::io()->confirm('Continue?')) {
    throw new \RuntimeException('User stopped upgrade');
  }

  // OK go!

  // The logic to toggle 'pathload' and `civimix-schema@5` is actually
  // a general/recurring update in CRM\CivixBundle\Builder\Module::save().
  // But it only applies if the $newClass has been set.

  if (!empty($oldClass)) {
    $gen->updateInfo(function(\CRM\CivixBundle\Builder\Info $info) use ($newClass) {
      $info->get()->upgrader = $newClass;
    });
  }
  foreach ($sqlFiles as $file) {
    $gen->removeFile($file);
  }

  Civix::boot(['output' => Civix::output()]);
  if (!empty($xmlSchemaFiles)) {
    \CRM\CivixBundle\Command\ConvertEntityCommand::convertEntities($xmlSchemaFiles, FALSE);
  }
};
