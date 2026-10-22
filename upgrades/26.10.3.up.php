<?php

use CRM\CivixBundle\Generator;

/**
 * Remove include-path mixin if present.
 *
 * It was too soon for this mixin to be optional, as leftover legacy code in CiviCRM-core actively
 * breaks autoloading, forcing us to keep all extensions on PHP's include_path for a while longer.
 * See https://github.com/civicrm/civicrm-core/pull/37656
 */
return function (Generator $gen) {
  if (\Civix::checker()->hasMixin('/^include-path@/')) {
    $gen->removeMixins(['include-path']);
  }
};
