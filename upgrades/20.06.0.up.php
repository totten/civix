<?php

/**
 * If you have a generated `phpunit.xml` or `phpunit.xml.dist` file, it may include the old option `syntaxCheck="false"`.
 * You can remove this.  The option has been inert and will raise errors in newer versions of PHPUnit.
 */
return function (\CRM\CivixBundle\Upgrader $upgrader) {
  /* @var \Symfony\Component\Console\Style\SymfonyStyle $io */
  $io = $upgrader->io;

  $files = array_filter([
    $upgrader->baseDir->string('phpunit.xml'),
    $upgrader->baseDir->string('phpunit.xml.dist'),
  ], 'file_exists');
  $upgrader->updateTextFiles($files, function(string $file, string $oldContent) use ($io, $upgrader) {
    $relFile = \CRM\CivixBundle\Utils\Files::relativize($file, $upgrader->baseDir->string() . '/');

    $content = $oldContent;
    $content = preg_replace(';(\s+)syntaxCheck="[^\"]+">;', '>', $content);
    $content = preg_replace(';(\s+)syntaxCheck=\'[^\']+\'>;', '>', $content);
    $content = preg_replace(';(\s+)syntaxCheck="[^\"]+"(\s+);', '\1', $content);
    $content = preg_replace(';(\s+)syntaxCheck=\'[^\']+\'(\s+);', '\1', $content);
    if ($content !== $oldContent) {
      $io->writeln("$relFile<info> includes obsolete option </info>syntaxCheck=\"...\"<info>. Removing it.</info> ");
    }
    return $content;
  });

};
