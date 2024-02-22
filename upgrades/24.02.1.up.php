<?php

use CRM\CivixBundle\Utils\Formatting;
use CRM\CivixBundle\Utils\Naming;

return function (\CRM\CivixBundle\Generator $gen) {

  $files = Civix::extDir()->search('glob:sql/auto_*.sql');
  $relFiles = array_map([\CRM\CivixBundle\Utils\Files::class, 'relativize'], $files);

  $oldClass = (string) $gen->infoXml->get()->upgrader;
  $newClass = sprintf('CiviMix\\Schema\\%s\\AutomaticUpgrader', Naming::createCamelName($gen->infoXml->getFile()));
  $delegateClass = Naming::createClassName($gen->infoXml->getNamespace(), 'Upgrader');

  $steps = [];
  if ($oldClass !== $newClass) {
    $steps[] = "Update info.xml to use $newClass";
  }
  if (!empty($relFiles)) {
    $steps[] = 'Delete ' . implode(' and ', $relFiles);
  }

  if (empty($steps)) {
    return;
  }

  $notes = [
    "This extension includes generated SQL files:\n\n" . Formatting::ul("%s\n", $relFiles),
    'These files are liable to install SQL tables incorrectly. The character-set and collation do not always match the user\'s CiviCRM database.',
    "Civix now supports automatic (on-the-fly) SQL generation, which ensures that new installations have proper collation. Enabling automatic SQL requires the following changes:\n\n" . Formatting::ol("%s\n", $steps),
  ];
  if ($oldClass === $newClass) {
    // OK
  }
  elseif ($oldClass === $delegateClass) {
    $notes[] = "AutomaticUpgrader will transparently call your existing $delegateClass, so any `upgrade_NNN()` functions will work the same. However, it is incompatible with some unusual customizations, such as:";
    $notes[] = Formatting::ul("%s\n", [
      'Manual SQL code in ' . implode(' or ', $relFiles),
      'Non-standard revision-tracking (e.g. Step-NNN classes)',
    ]);
  }
  else {
    $notes[] = "However, your extension has an unrecognized upgrader ($oldClass) which may not be compatible.";
  }

  $notes[] = "You may skip this update. However, in the future, you will be responsible for keeping the SQL files up-to-date.";
  \Civix::io()->note($notes);

  $actions = [
    'y' => 'Yes, apply update. Use AutomaticUpgrader to generate SQL.',
    'n' => 'No, skip update. Keep current SQL files.',
    'a' => 'Abort',
  ];
  $action = Civix::io()->choice("Should we apply the update?", $actions, 'y');
  if ($action === 'a') {
    throw new \RuntimeException('User stopped upgrade');
  }
  if ($action === 'n') {
    return;
  }

  // The logic to toggle 'pathload' and `civimix-schema@5` is actually
  // a general/recurring update in CRM\CivixBundle\Builder\Module::save().
  // But it only applies if the $newClass has been set.

  $gen->updateInfo(function(\CRM\CivixBundle\Builder\Info $info) use ($newClass) {
    $info->get()->upgrader = $newClass;
  });
  foreach ($files as $file) {
    $gen->removeFile($file);
  }

};
