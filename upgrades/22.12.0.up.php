<?php

/**
 * Upgrade hook_civicrm_entityTypes to use mixin
 */
return function (\CRM\CivixBundle\Upgrader $upgrader) {

  $prefix = $upgrader->infoXml->getFile();

  if (is_dir($upgrader->baseDir->string('xml/schema/CRM'))) {
    $upgrader->addMixins(['entity-types-php@1.0']);
  }

  $upgrader->removeHookDelegation([
    "_{$prefix}_civix_civicrm_entityTypes",
  ]);

};
