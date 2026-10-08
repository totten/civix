<?php

/**
 * Remove obsolete hook_civicrm_navigationMenu delegation.
 *
 * These functions were unused dead code since 4.7.
 */
return function (\CRM\CivixBundle\Generator $gen) {
  $prefix = $gen->infoXml->getFile();

  $gen->removeHookDelegation([
    "_{$prefix}_civix_navigationMenu",
    // These 2 are internal and shouldn't have been called outside the civix.php file, but just to be safe:
    "_{$prefix}_civix_fixNavigationMenu",
    "_{$prefix}_civix_fixNavigationMenuItems",
  ]);

  $gen->cleanEmptyHooks();
};
