<?php

use CRM\CivixBundle\Utils\MixinLibraries;

/**
 * Earlier civix revisions would generate `civimix-schema@X.X.X.phar`.
 * This has compatibility issues with Backdrop.
 * Convert to `civimix-schema@X.X.X/`.
 */
return function (\CRM\CivixBundle\Generator $gen) {

  if ($gen->baseDir->search('glob:mixin/lib/civimix-schema@5.*.phar')) {
    $io = Civix::io();
    $io->title("Convert civimix-schema@5");

    $gen->updateMixinLibraries(function (MixinLibraries $mixinLibraries): void {
      $mixinLibraries->remove('civimix-schema@5');
      $mixinLibraries->add('civimix-schema@5');
    });
  }

};
