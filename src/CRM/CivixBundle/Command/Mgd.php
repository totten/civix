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

}
