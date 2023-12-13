<?php

/**
 * Upgrade hook_civicrm_entityTypes to use mixin
 */
return function (\CRM\CivixBundle\Generator $gen) {

  $prefix = $gen->infoXml->getFile();

  if (is_dir($gen->baseDir->string('xml/schema/CRM'))) {
    \Civix::io()->note([
      'Civix 23.02 removes `*_civix_civicrm_entityTypes` in favor of a mixin `entity-types-php@1.0`.',
      'This reduces code-duplication and will enable easier updates in the future.',
      'This may raise the minimum requirements to CiviCRM v5.45.',
    ]);
    if (!\Civix::io()->confirm('Continue with upgrade?')) {
      throw new \RuntimeException('User stopped upgrade');
    }

    $gen->addMixins(['entity-types-php@1.0']);
  }

  $gen->removeHookDelegation([
    "_{$prefix}_civix_civicrm_entityTypes",
  ]);

};
