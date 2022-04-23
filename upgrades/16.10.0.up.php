<?php
/**
 * As long as `CRM_*_Upgrader` classes are wired-up via lifecycle hooks (`hook_install`, etc), we
 * should include `hook_postInstall`.
 *
 * At some point in the future, this step could be removed if we configure `info.xml`'s `<upgrader>` option.
 */
return function (\CRM\CivixBundle\Upgrader $upgrader) {

  $upgrader->addHookDelegation('civicrm_postInstall', '',
    'If you use civix to facilitate database upgrades ("civix generate:upgrader"), then you should enable this stub. Otherwise, it is not needed.');

};
