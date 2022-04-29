<?php
/**
 * Civix-based modules should pass metadata about custom database entities through hook_civicrm_entityTypes.
 *
 * In the future, it would be great to remove this delegate and use a mixin.
 *
 * However, `hook_civicrm_entityTypes` is a very unusual hook that can fire during bootstrap,
 * and it may not be amenable. We'll support it as a hook-delegation-stub until an alternative
 * is available.
 */
return function (\CRM\CivixBundle\Upgrader $upgrader) {

  $upgrader->addHookDelegation('civicrm_entityTypes', '&$entityTypes',
    'If you use civix to generate custom entities, then you will need this stub. Otherwise, it is not needed.');

};
