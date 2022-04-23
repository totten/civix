<?php

return function (\CRM\CivixBundle\Upgrader $upgrader) {
  /* @var \Symfony\Component\Console\Style\SymfonyStyle $io */
  $io = $upgrader->io;

  $testFiles = \CRM\CivixBundle\Utils\Files::findFiles($upgrader->baseDir->string('tests'), '*.php');
  $oldTestFiles = [];
  foreach ($testFiles as $testFile) {
    $content = file_get_contents($testFile);
    if (preg_match('/PHPUnit_Framework_/', $content)) {
      $oldTestFiles[] = \CRM\CivixBundle\Utils\Files::relativize($testFile, $upgrader->baseDir->string() . '/');
    }
  }

  if (!empty($oldTestFiles)) {
    $io->note([
      'PHPUnit changed the name of its base-class circa PHPUnit v5:',
      "OLD: PHPUnit_Framework_TestCase\nNEW: PHPUnit\Framework\TestCase",
      'This change occurred several years ago (before civix had automated upgrades).',
      'You will need to edit these files manually after upgrade:',
      '* ' . implode("\n* ", $oldTestFiles),
    ]);
    if (!$io->confirm('Continue with upgrade?')) {
      throw new \RuntimeException('User stopped upgrade');
    }

    // Note: If anyone cares about this, it's patch-welcome for a more complete fix.
    // But given that the transition was a few years ago, I'm not expecting it to be common.
  }

};
