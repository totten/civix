<?php
/**
 * As long as `CRM_*_Upgrader` classes are wired-up via lifecycle hooks (`hook_install`, etc), we
 * should include `hook_postInstall`.
 *
 * At some point in the future, this step could be removed if we configure `info.xml`'s `<upgrader>` option.
 */
return function (\CRM\CivixBundle\Upgrader $upgrader) {
  $io = $upgrader->io;

  // Give a notice if the new `CRM/*/Upgrader/Base` has a substantive change.
  // Note: The change is actually done in the generic regen. This is just a notice.
  $phpBaseClass = \CRM\CivixBundle\Utils\Naming::createClassName($upgrader->infoXml->getNamespace(), 'Upgrader', 'Base');
  $phpBaseFile = \CRM\CivixBundle\Utils\Naming::createClassFile($upgrader->infoXml->getNamespace(), 'Upgrader', 'Base');
  if (file_exists($phpBaseFile)) {
    $content = file_get_contents($phpBaseFile);
    if (preg_match('|CRM_Core_BAO_Setting::setItem\(.revision, *.Extension.|', $content)) {
      $io->note([
        "$phpBaseClass is based on a very old template.",
        "When $phpBaseClass is regenerated, it will transition an important data element from \"civicrm_setting\" to \"civicrm_extension\".",
        "Several extensions have made this transition, and there are no known issues.",
        "See also: https://issues.civicrm.org/jira/browse/CRM-19252",
      ]);
      if (!$io->confirm('Continue with upgrade?')) {
        throw new \RuntimeException('User stopped upgrade');
      }
    }

    $upgrader->addHookDelegation('civicrm_postInstall', '',
      "This hook is important for supporting the new version of $phpBaseClass.");
  }
  else {
    $upgrader->addHookDelegation('civicrm_postInstall', '',
      'If you use civix to facilitate database upgrades ("civix generate:upgrader"), then you should enable this stub. Otherwise, it is not needed.');
  }

};
