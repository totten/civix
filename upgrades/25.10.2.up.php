<?php

use CRM\CivixBundle\Utils\Formatting;

/**
 * Upgrade mgd-php@1 to mgd-php@2
 */
return function (\CRM\CivixBundle\Generator $gen) {
  $checker = \Civix::checker();
  if (!$checker->hasMixin('/^mgd-php@1/')) {
    return;
  }

  $mgdFiles = $gen->baseDir->search('find:*.mgd.php');
  $mgdFiles = array_map(fn($p) => \CRM\CivixBundle\Utils\Files::relativize($p, $gen->baseDir->string()), $mgdFiles);
  $mgdFiles = str_replace(DIRECTORY_SEPARATOR, '/', $mgdFiles);
  $mgdFiles = preg_replace(';^./;', '', $mgdFiles);

  $oddballs = array_values(array_filter($mgdFiles, function ($mgd) {
    if (basename($mgd) === $mgd || preg_match(';^(managed|api|CRM|Civi)/;', $mgd)) {
      return FALSE;
    }
    return TRUE;
  }));

  if (empty($oddballs)) {
    Civix::io()->note('Upgrade mgd-php@1 to mgd-php@2');
    $gen->addMixins(['mgd-php@2']);
    return;
  }

  Civix::io()->note([
    'The mixin mgd-php@1 should be upgraded to mgd-php@2. This reduces the scope of the file-search, which should prevent bugs and slightly improve performance.',
    'Specifically, mgd-php@2 will search for *.mgd.php files in the conventional locations:',
    Formatting::ul("%s\n", [
      '*.mgd.php (root folder)',
      'managed/**.mgd.php',
      'api/**.mgd.php',
      'CRM/**.mgd.php',
      'Civi/**.mgd.php',
    ]),
    'However, this extension includes some files in unconventional locations:',
    Formatting::ul("%s\n", $oddballs),
    'If you apply the upgrade to mgd-php@2, then you will need to move these files manually. (Move them to ./managed or another conventional folder.)',
    'If you skip the upgrade, then the files will work for now. However, future code-generators may activate mgd-php@2 anyway.',
  ]);

  $choice = \Civix::io()->choice('Should we apply the upgrade to mgd-php@2?', [
    'y' => 'Yes, upgrade to mgd-php@2',
    'n' => 'No, keep mgd-php@1',
    'a' => 'Abort',
  ], 'n');

  switch ($choice) {
    case 'y':
      $gen->addMixins(['mgd-php@2']);
      break;

    case 'n':
      // Nothing to do
      break;

    case 'a':
      throw new \RuntimeException('User stopped upgrade');
  }

};
