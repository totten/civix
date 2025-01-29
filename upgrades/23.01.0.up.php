<?php
use CRM\CivixBundle\Utils\Formatting;

return function (\CRM\CivixBundle\Generator $gen) {
  /* @var \Symfony\Component\Console\Style\OutputStyle $io */
  $io = \Civix::io();

  $previewChanges = [];
  $previewChanges[] = ['*_civix_civicrm_config', 'Remove Smarty boilerplate'];

  // Do we need a mixin like `ang-php`? Maybe... check whether we have files like `*.ang.php`.
  $filePatterns = [
    'find:*.tpl' => 'smarty-v2@1.0.0',
  ];
  $mixins = array_filter($filePatterns,
    function (string $mixin, string $pattern) use ($gen, $io, &$previewChanges) {
      $flagFiles = $gen->baseDir->search($pattern);
      $previewChanges[] = [
        'info.xml',
        $flagFiles ? "Enable $mixin" : "Skip $mixin. (No files match \"$pattern\")",
      ];
      return (bool) $flagFiles;
    },
    ARRAY_FILTER_USE_BOTH
  );

  $io->note([
    "Civix v23.01 simplifies the boilerplate used for Smarty registration.",
    "The following may be affected:",
    Formatting::ol("%-31s %s\n", $previewChanges),
  ]);

  if (!empty($mixins)) {
    $io->warning([
      "If Smarty templates are used by any lifecycle hooks (install,enable,disable,uninstall,upgrade,managed), then please re-test them.",
    ]);
  }

  if (!$io->confirm('Continue with upgrade?')) {
    throw new \RuntimeException('User stopped upgrade');
  }

  $gen->addMixins($mixins);

};
