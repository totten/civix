<?php

/**
 * I haven't traced why, but it seems that core is getting more eager to scan
 * BAOs/DAOs early on -- in a way that provokes class-loading errors if your
 * extension defines entities and lacks `<psr0>` for `CRM_`
 *
 * Just add the '<psr0>` bit to everything.
 */
return function (\CRM\CivixBundle\Generator $gen) {

  $gen->updateInfo(function (\CRM\CivixBundle\Builder\Info $info) use ($gen) {
    /* @var \Symfony\Component\Console\Style\SymfonyStyle $io */
    $io = \Civix::io();

    $loaders = $info->getClassloaders();
    $prefixes = array_column($loaders, 'prefix');
    if (file_exists($gen->baseDir->string('CRM')) && !in_array('CRM_', $prefixes)) {
      $io->section('"CRM" Class-loader');
      $io->note([
        'Older templates enabled class-loading via "hook_config" and "include_path".',
        'Newer templates enable class-loading via "info.xml" ("<classloader>"). This fixes some edge-case issues and allows more configuration.',
        'It is generally safe for these loaders to coexist. The upgrade will add "<classloader>" for the "CRM" folder.',
      ]);
      $io->warning([
        'If you use the rare (and ill-advised/unsupported) practice of class-overrides, then you may need extra diligence with any changes to class-loading.',
      ]);

      if (!$io->confirm('Continue with upgrade?')) {
        throw new \RuntimeException('User stopped upgrade');
      }
      $loaders[] = ['type' => 'psr0', 'prefix' => 'CRM_', 'path' => '.'];
      $info->setClassLoaders($loaders);
    }

  });

};
