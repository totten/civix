#!/usr/bin/env php
<?php

// This is a sniff-test to ensure that the generated PHAR looks the way it
// should. In particular, we assert that some classes MUST have
// namespace-prefixes, and other classes MUST NOT.

global $errors, $pharFile;

if (empty($argv[1]) || !file_exists($argv[1])) {
  die("Missing argument. Ex: check-phar.php /path/to/my.phar");
}

$pharFile = $argv[1];
$errors = [];

assertMatch('src/CRM/CivixBundle/Command/AddPageCommand.php', ';^namespace CRM.CivixBundle.Command;');
assertNotMatch('src/CRM/CivixBundle/Command/AddPageCommand.php', ';^namespace CivixPhar;');

assertNotMatch('vendor/symfony/console/Input/InputInterface.php', ';^namespace Symfony;');
assertMatch('vendor/symfony/console/Input/InputInterface.php', ';^namespace CivixPhar.Symfony;');

assertMatch('src/CRM/CivixBundle/Services.php', ';civicrm_api3;');
assertNotMatch('src/CRM/CivixBundle/Services.php', ';CivixPhar.civicrm_api3;');

assertMatch('vendor/civicrm/cv-lib/src/CmsBootstrap.php', ';JFactory::;');
assertMatch('vendor/civicrm/cv-lib/src/CmsBootstrap.php', ';Drupal::;');
assertMatch('vendor/civicrm/cv-lib/src/CmsBootstrap.php', ';drupal_bootstrap;');
assertMatch('vendor/civicrm/cv-lib/src/CmsBootstrap.php', ';user_load;');
assertMatch('vendor/civicrm/cv-lib/src/CmsBootstrap.php', ';wp_set_current_user;');
foreach (['vendor/civicrm/cv-lib/src/Bootstrap.php', 'vendor/civicrm/cv-lib/src/CmsBootstrap.php'] as $file) {
  // These two files have lots of CMS symbols. The only thing that should be prefixed is Symfony stuff.
  $allPrefixed = grepFile($file, ';CivixPhar;');
  $expectPrefixed = grepFile($file, ';CivixPhar.*(Symfony|Psr.*Log);');
  if ($allPrefixed !== $expectPrefixed) {
    $errors[] = "File $file has lines with unexpected prefixing:\n  " . implode("\n  ", array_diff($allPrefixed, $expectPrefixed)) . "\n";
  }
}
assertNotMatch('vendor/civicrm/cv-lib/src/CmsBootstrap.php', ';CivixPhar.JFactory;');
assertNotMatch('vendor/civicrm/cv-lib/src/CmsBootstrap.php', ';CivixPhar.Drupal;');
assertNotMatch('vendor/civicrm/cv-lib/src/CmsBootstrap.php', ';CivixPhar..?Symfony..?Component..?HttpFoundation;');

if (empty($errors)) {
  echo "OK $pharFile\n";
}
else {
  echo "ERROR $pharFile\n";
  echo implode("", $errors);
  exit(1);
}

########################################################################################

/**
 * Construct full name for a file (within the phar).
 */
function getFilename(?string $relpath = NULL): string {
  global $pharFile;
  $path = 'phar://' . $pharFile;
  if ($relpath != NULL) {
    $path .= '/' . $relpath;
  }
  return $path;
}

function assertMatch(string $relpath, string $regex) {
  global $errors;
  $content = explode("\n", file_get_contents(getFilename($relpath)));
  if (!preg_grep($regex, $content)) {
    $errors[] = sprintf("Failed: assertMatch(%s, %s)\n", var_export($relpath, 1), var_export($regex, 1));
  }
}

function assertNotMatch(string $relpath, string $regex) {
  global $errors;
  $content = explode("\n", file_get_contents(getFilename($relpath)));
  if (!empty($x = preg_grep($regex, $content))) {
    $errors[] = sprintf("Failed: assertNotMatch(%s, %s)\n", var_export($relpath, 1), var_export($regex, 1));
  }
}

function grepFile(string $relpath, string $regex): array {
  $content = explode("\n", file_get_contents(getFilename($relpath)));
  return preg_grep($regex, $content);
}
