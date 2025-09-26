<?php

/**
 * Upgrade Api4 classes to use scan-classes mixin
 */
return function (\CRM\CivixBundle\Generator $gen) {

  $checker = \Civix::checker();

  if (is_dir($gen->baseDir->string('Civi/Api4')) && !$checker->hasMixin('/^scan-classes@/')) {
    \Civix::io()->note([
      'Extensions with Api4 classes should use the mixin `scan-classes`.',
      'This will automatically add `scan-classes@1.0` since this extension has a Civi/Api4 directory.',
      'See https://github.com/civicrm/civicrm-core/pull/33371',
    ]);
    if (!\Civix::io()->confirm('Continue with upgrade?')) {
      throw new \RuntimeException('User stopped upgrade');
    }

    $gen->addMixins(['scan-classes@1.0']);
  }

};
