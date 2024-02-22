<?php

use CRM\CivixBundle\Utils\Formatting;

return function (\CRM\CivixBundle\Generator $gen) {

  // The logic to toggle 'pathload' and `civimix-schema@5` is actually
  // a general/recurring update in CRM\CivixBundle\Builder\Module::save().
  // This merely serves as a notice that it will happen.

  $steps = [];
  if (!Civix::checker()->coreHasPathload()) {
    $steps[] = 'Create mixin/lib/pathload-0.php';
  }
  if (!Civix::checker()->coreProvidesLibrary('civimix-schema@5')) {
    $steps[] = 'Create mixin/lib/' . basename($gen->mixinLibraries->available['civimix-schema@5']->file);
  }

  if (\Civix::checker()->hasUpgrader() && $steps) {
    \Civix::io()->note([
      "This update adds a new helper, E::schema(), which requires the library civimix-schema@5. To enable support for older versions of CiviCRM (<5.73), the update will:\n\n" . Formatting::ol("%s\n", $steps),
    ]);
  }

};
