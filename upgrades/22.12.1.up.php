<?php

use CRM\CivixBundle\Utils\Naming;

/**
 * v5.38 supports an <upgrader> tag and a common base class. Updates:
 * - Remove hook delegations from module.php.
 * - Add <upgrader> to info.xml
 * - Use core's base class
 * - Remove old base class
 */
return function (\CRM\CivixBundle\Upgrader $upgrader) {
  $io = $upgrader->io;
  $io->section('Lifecycle Hooks: Install, Upgrade, etc');

  $MIN_COMPAT = '5.38';
  $nameSpace = $upgrader->infoXml->getNamespace();
  $upgraderClass = Naming::createClassName($nameSpace, 'Upgrader');
  $upgraderFile = Naming::createClassFile($nameSpace, 'Upgrader');
  $upgraderBaseClass = Naming::createClassName($nameSpace, 'Upgrader', 'Base');
  $upgraderBaseFile = Naming::createClassFile($nameSpace, 'Upgrader', 'Base');
  // $upgraderFile = $upgrader->baseDir->string(Naming::createClassFile($nameSpace, 'Upgrader'));
  // $upgraderBaseFile = $upgrader->baseDir->string(Naming::createClassFile($nameSpace, 'Upgrader', 'Base'));
  $hasUpgrader = file_exists($upgraderFile);

  $notes = [];
  $notes[] = 'Old templates included ~10 boilerplate functions to handle lifecycle events (hook_install, hook_upgrade, hook_uninstall, etc).';
  $notes[] = $hasUpgrader
    ? "With CiviCRM $MIN_COMPAT+, these can be simplified or removed. Instead, we will use the \"info.xml\" directive for \"<upgrader>\"."
    : 'Much of this boilerplate can be simplified or removed.';
  $io->note($notes);

  if ($hasUpgrader && version_compare($upgrader->infoXml->getCompatibilityVer(), $MIN_COMPAT, '<')) {
    $io->warning("The minimum required version of CiviCRM will increase to $MIN_COMPAT.");
  }

  if (!$io->confirm('Continue with upgrade?')) {
    throw new \RuntimeException('User stopped upgrade');
  }

  $prefix = $upgrader->infoXml->getFile();
  $upgrader->removeHookDelegation([
    // Needed by mixin-polyfill: "_{$prefix}_civix_civicrm_install",
    "_{$prefix}_civix_civicrm_postInstall",
    "_{$prefix}_civix_civicrm_uninstall",
    // Needed by mixin-polyfill: "_{$prefix}_civix_civicrm_enable",
    "_{$prefix}_civix_civicrm_disable",
    "_{$prefix}_civix_civicrm_upgrade",
  ]);

  if ($hasUpgrader) {
    $upgrader->updateInfo(function(\CRM\CivixBundle\Builder\Info $info) use ($MIN_COMPAT, $upgraderClass) {
      $info->raiseCompatibilityMinimum($MIN_COMPAT);
      // Add <upgrader> tag
      if (!$info->get()->xpath('upgrader')) {
        $info->get()->addChild('upgrader', $upgraderClass);
      }
    });
    // Switch base class
    $upgrader->updateTextFiles([$upgraderFile], function(string $file, string $content) use ($upgraderBaseClass) {
      return str_replace($upgraderBaseClass, 'CRM_Extension_Upgrader_Base', $content);
    });
  }

  if (file_exists($upgraderBaseFile)) {
    unlink($upgraderBaseFile);
  }

};
