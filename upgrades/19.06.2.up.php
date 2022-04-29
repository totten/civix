<?php

/**
 * The templates for PHPUnit tests have been updated to match a major
 * transition in PHPUnit -- *all upstream base-classes were renamed*:
 *
 * - `PHPUnit_Framework_TestCase` is the base-class in PHPUnit 4 and earlier
 * - `\PHPUnit\Framework\TestCase` is the base-class in PHPUnit 6 and later
 * - PHPUnit 5 is a transitional version which supports both naming conventions.
 *
 * In recent years, documentation+tooling in Civi have encouraged usage of
 * PHPUnit 5, so (hopefully) most environments are compatible with the newer naming.
 *
 * Going forward, `civix` will generate templates using the newer naming.
 *
 * To be consistent and forward-compatible, you should consider updating your
 * existing unit-tests to use the name base-classes.
 */
return function (\CRM\CivixBundle\Upgrader $upgrader) {
  /* @var \Symfony\Component\Console\Style\SymfonyStyle $io */
  $io = $upgrader->io;

  $testFiles = \CRM\CivixBundle\Utils\Files::findFiles($upgrader->baseDir->string('tests'), '*.php');
  $upgrader->updateTextFiles($testFiles, function(string $file, string $content) use ($io, $upgrader) {
    $old = 'PHPUnit_Framework_TestCase';
    $new = 'PHPUnit\Framework\TestCase';
    $relFile = \CRM\CivixBundle\Utils\Files::relativize($file, $upgrader->baseDir->string() . '/');

    if (strpos($content, $old) === FALSE) {
      return $content;
    }

    $io->writeln("<info>PHPUnit 6.x+ changed the name of the standard base-class (</info>$old<info> => </info>$new<info>).</info>");
    $io->writeln("<info>The file </info>$relFile<info> contains at least one reference to the old name.</info>");
    $io->writeln("<info>The upgrader can do an automatic search-replace on this file.</info>");
    if ($io->confirm("Perform search/replace?")) {
      $content = str_replace($old, $new, $content);
    }
    return $content;
  });

};
