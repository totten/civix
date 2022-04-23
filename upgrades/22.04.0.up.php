<?php

return function (\CRM\CivixBundle\Upgrader $upgrader) {
  /* @var \Symfony\Component\Console\Style\SymfonyStyle $io */
  $io = $upgrader->io;
  $prefix = $upgrader->infoXml->getFile();

  $io->note([
    "Civix v21.09 (and earlier) used several boilerplate functions like \"_*_civix_civicrm_managed()\".",
    "Civix v22.04 converts these to mixins. This reduces code-duplication and will enable easier updates in the future.",
    "The upgrader will examine your extension, remove old function-calls, and enable mixins (if needed).",
    "The following functions+mixins may be affected:\n\n" . implode("\n", [
      "1. _*_civix_civicrm_angularModules()        =>  ang-php@1.0.0",
      "2. _*_civix_civicrm_managed()               =>  mgd-php@1.0.0",
      "3. _*_civix_civicrm_alterSettingsFolders()  =>  setting-php@1.0.0",
      "4. _*_civix_civicrm_caseTypes()             =>  case-xml@1.0.0",
      "5. _*_civix_civicrm_xmlMenu()               =>  menu-xml@1.0.0",
      "6. _*_civix_civicrm_themes()                =>  theme-php@1.0.0",
    ]),
  ]);

  if (!$io->confirm('Proceed?')) {
    throw new \RuntimeException('User stopped upgrade');
  }

  // Do we need a mixin like `ang-php`? Maybe... check whether we have files like `*.ang.php`.
  $filePatterns = [
    'glob:ang/*.ang.php' => 'ang-php@1.0.0',
    'find:*.mgd.php' => 'mgd-php@1.0.0',
    'glob:settings/*.setting.php' => 'setting-php@1.0.0',
    'glob:xml/case/*.xml' => 'case-xml@1.0.0',
    'glob:xml/Menu/*.xml' => 'menu-xml@1.0.0',
    'glob:*.theme.php' => 'theme-php@1.0.0',
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

  $upgrader->removeHookDelegation([
    "_{$prefix}_civix_civicrm_angularModules",
    "_{$prefix}_civix_civicrm_managed",
    "_{$prefix}_civix_civicrm_alterSettingsFolders",
    "_{$prefix}_civix_civicrm_caseTypes",
    "_{$prefix}_civix_civicrm_xmlMenu",
    "_{$prefix}_civix_civicrm_themes",
  ]);

};
