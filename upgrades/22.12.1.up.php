<?php

use CRM\CivixBundle\Utils\Files;
use CRM\CivixBundle\Utils\Formatting;
use CRM\CivixBundle\Utils\Naming;

/**
 * v5.38 supports an <upgrader> tag and a common base class. Updates:
 * - Remove hook delegations from module.php.
 * - Add <upgrader> to info.xml
 * - Use core's base class
 * - Remove old base class
 */
return function (\CRM\CivixBundle\Generator $gen) {
  $io = \Civix::io();
  $io->section('Lifecycle Hooks: Install, Upgrade, etc');

  $info = $gen->infoXml;
  $MIN_COMPAT = '5.38';
  $oldCompat = $info->getCompatibilityVer();
  $nameSpace = $info->getNamespace();
  $mainFile = $gen->baseDir->string($info->getFile() . '.php');
  $upgraderClass = Naming::createClassName($nameSpace, 'Upgrader');
  $upgraderFile = Naming::createClassFile($nameSpace, 'Upgrader');
  $upgraderBaseClass = Naming::createClassName($nameSpace, 'Upgrader', 'Base');
  $upgraderBaseFile = Naming::createClassFile($nameSpace, 'Upgrader', 'Base');
  $hasUpgrader = file_exists($upgraderFile);

  $previewChanges = [];
  if ($hasUpgrader) {
    $previewChanges[] = ['info.xml', 'Fill-in <upgrader> tag'];
    $previewChanges[] = [$upgraderClass, 'Use new base-class'];
    $previewChanges[] = [$upgraderBaseClass, 'Remove old base-class'];
  }
  $previewChanges[] = ['*_civix_civicrm_install()', 'Simplify boilerplate'];
  $previewChanges[] = ['*_civix_civicrm_postInstall()', 'Remove boilerplate'];
  $previewChanges[] = ['*_civix_civicrm_enable()', 'Simplify boilerplate'];
  $previewChanges[] = ['*_civix_civicrm_upgrade()', 'Remove boilerplate'];
  $previewChanges[] = ['*_civix_civicrm_disable()', 'Remove boilerplate'];
  $previewChanges[] = ['*_civix_civicrm_uninstall()', 'Remove boilerplate'];

  $io->note([
    'Civix v22.12 simplifies the boilerplate code used for install/upgrade/uninstall (etc).',
    'The following may be affected:',
    Formatting::ol("%-31s %s\n", $previewChanges),
  ]);

  if ($hasUpgrader && version_compare($oldCompat, $MIN_COMPAT, '<')) {
    $io->warning("This relies on functionality added by CiviCRM v5.38 (June 2021). The minimum requirements will increase automatically.");
  }

  // The `hook_install`, `hook_uninstall`, etc will no longer fire `_mymodule_civix_civicrm_config()`.
  // For class-loading, this should be OK - since we now have PSR-0 enabled. But Smarty could
  // potentially break. Except... in practice... I could only find 2 ext's in universe that might
  // be impacted. So won't try hard to change them -- we'll merely warn.
  if (!empty(glob('sql/*.tpl'))
    || Files::grepFiles(';smarty;i', [$upgraderFile])
    || Files::grepFiles(';\\.tpl;i', [$upgraderFile])
    || Files::grepFiles(';execute.*Template;i', [$upgraderFile])
    || Files::grepFiles(';civicrm_install.*smarty;si', [$mainFile])
    || Files::grepFiles(';civicrm_upgrade.*smarty;si', [$mainFile])
    || Files::grepFiles(';civicrm_install.*\.tpl;si', [$mainFile])
    || Files::grepFiles(';civicrm_upgrade.*\.tpl;si', [$mainFile])
  ) {
    $io->warning('If you use the uncommon practice of calling Smarty during installation/upgrade, then you should review/retest these steps. Determine whether the Smarty include-path is sufficiently up-to-date.');
  }

  if (!$io->confirm('Continue with upgrade?')) {
    throw new \RuntimeException('User stopped upgrade');
  }

  $prefix = $info->getFile();
  $gen->removeHookDelegation([
    "_{$prefix}_civix_civicrm_postInstall",
    "_{$prefix}_civix_civicrm_uninstall",
    "_{$prefix}_civix_civicrm_disable",
    "_{$prefix}_civix_civicrm_upgrade",
  ]);

  if ($hasUpgrader) {
    $gen->updateInfo(function(\CRM\CivixBundle\Builder\Info $info) use ($MIN_COMPAT, $upgraderClass) {
      $info->raiseCompatibilityMinimum($MIN_COMPAT);
      // Add <upgrader> tag
      if (!$info->get()->xpath('upgrader')) {
        $info->get()->addChild('upgrader', $upgraderClass);
      }
    });
    // Switch base class
    $gen->updateTextFiles([$upgraderFile], function(string $file, string $content) use ($upgraderBaseClass) {
      return str_replace($upgraderBaseClass, 'CRM_Extension_Upgrader_Base', $content);
    });
  }

  if (file_exists($upgraderBaseFile)) {
    unlink($upgraderBaseFile);
  }

};
