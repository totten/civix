<?php

return function (\CRM\CivixBundle\Upgrader $upgrader) {
  /* @var \Symfony\Component\Console\Style\SymfonyStyle $io */
  $io = $upgrader->io;
  $prefix = $upgrader->infoXml->getFile();

  $io->note([
    "Civix v23.01 updates the Smarty registration:",
    "- Previously, Smarty boilerplate was added to \"hook_config\" unconditionally.\n" .
    "- Now, the mixin \"smarty-v2\" will be enabled if needed.",
    "This change reduces boilerplate and improves maintainability.",
  ]);

  $io->warning([
    "If this extension uses Smarty with a lifecycle hook (install,enable,disable,uninstall,upgrade), then please re-test them.",
  ]);

  if (!$io->confirm('Continue with upgrade?')) {
    throw new \RuntimeException('User stopped upgrade');
  }

  // Do we need a mixin like `ang-php`? Maybe... check whether we have files like `*.ang.php`.
  $filePatterns = [
    'find:*.tpl' => 'smarty-v2@1.0.0',
  ];
  $mixins = array_filter($filePatterns,
    function (string $mixin, string $pattern) use ($upgrader, $io) {
      $flagFiles = $upgrader->baseDir->search($pattern);
      $io->note($flagFiles
        ? "Enable \"$mixin\". There are files matching pattern \"$pattern\"."
        : "Skip \"$mixin\". There are no files matching pattern \"$pattern\"."
      );
      return (bool) $flagFiles;
    },
    ARRAY_FILTER_USE_BOTH
  );
  $upgrader->addMixins($mixins);

};
