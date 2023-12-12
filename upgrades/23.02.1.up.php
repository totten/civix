<?php

/**
 * Circa v19.11.0, it became a default to add the psr4 rule for "Civi" folders.
 * However, as older extensions adopt newer technologies (like `Civi\Api4`), it helps
 * to add a similar to them.
 */
return function (\CRM\CivixBundle\Upgrader $upgrader) {

  $upgrader->updateInfo(function (\CRM\CivixBundle\Builder\Info $info) use ($upgrader) {
    /* @var \Symfony\Component\Console\Style\SymfonyStyle $io */
    $io = \Civix::io();

    $loaders = $info->getClassloaders();
    $prefixes = array_column($loaders, 'prefix');
    if (!in_array('Civi\\', $prefixes)) {
      $io->section('"Civi" Class-loader');
      $io->note([
        'Technologies like APIv4 may require you to use the "Civi" namespace, which is not enabled on this extension.',
        'You can automatically add the "Civi" namespace now to get prepared, or you can skip this.',
        '(If you change your mind later, then simply edit "info.xml" to add or remove "<classloader>" rules.)',
      ]);

      if ($io->confirm('Add the "Civi" namespace?')) {
        $loaders[] = ['type' => 'psr4', 'prefix' => 'Civi\\', 'path' => 'Civi'];
        $info->setClassLoaders($loaders);
      }
    }

  });

};
