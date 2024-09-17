<?php

use CRM\CivixBundle\Utils\Formatting;
use CRM\CivixBundle\Utils\Naming;

return function (\CRM\CivixBundle\Generator $gen) {

  $sqlFiles = Civix::extDir()->search('glob:sql/auto_*.sql');
  $relSqlFiles = array_map([\CRM\CivixBundle\Utils\Files::class, 'relativize'], $sqlFiles);

  $oldClass = (string) $gen->infoXml->get()->upgrader;
  $newClass = sprintf('CiviMix\\Schema\\%s\\AutomaticUpgrader', Naming::createCamelName($gen->infoXml->getFile()));
  $delegateClass = Naming::createClassName($gen->infoXml->getNamespace(), 'Upgrader');

  $steps = [];
  $xmlSchemaFiles = civix::extDir()->search('find:xml/schema/*.xml');
  if (!empty($xmlSchemaFiles)) {
    $steps[] = "Convert xml/schema/*.xml to schema/*.entityType.php";
    $steps[] = "Regenerate CRM/*/DAO/*.php";
    $steps[] = "Update mixin entity-types-php@1 to entity-types-php@2";
  }

  if ($oldClass && $oldClass !== $newClass) {
    $steps[] = "Update info.xml to use $newClass (which delegates to $delegateClass)";
  }
  else {
    $steps[] = "Update info.xml to use $newClass";
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

  $notes = [
    "This update converts data-storage from Entity Framework v1 (EFv1) to Entity Framework v2 (EFv2).",
    "EFv2 stores schema as *.php. It has simpler workflows and less boilerplate. SQL is generated during installation. (More details: https://github.com/totten/civix/wiki/Entity-Templates)",
    // "For a full comparison, see: https://github.com/totten/civix/wiki/Entity-Templates",
    "The upgrader will make the following changes:\n\n" . Formatting::ol("%s\n", $steps),
    "This should work for many extensions, but it should be tested. You may encounter issues if any of these scenarios apply:\n\n" . Formatting::ol("%s\n", $warnings),
    "You may skip this update. However, going forward, civix will only support EFv2. You will be responsible for maintaining any boilerplate for EFv1.",
  ];

  Civix::io()->title("Entity Framework v1 => v2");
  Civix::io()->note($notes);

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
  \CRM\CivixBundle\Command\ConvertEntityCommand::convertEntities($xmlSchemaFiles, FALSE);

};
