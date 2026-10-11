<?php

use CRM\CivixBundle\Generator;
use CRM\CivixBundle\Utils\Files;
use CRM\CivixBundle\Utils\Naming;

/**
 * Replace PHP include_path boilerplate in *.civix.php with include-path@1 mixin.
 * Recommends adding the mixin if needed; advises it's probably not needed otherwise,
 * defaulting the prompt to 'y' if needed, or 'n' if not needed.
 */
return function (Generator $gen) {
  $io = \Civix::io();
  $info = $gen->infoXml;

  if (\Civix::checker()->hasMixin('/^include-path@/')) {
    return;
  }

  $reasons = [];

  // 1. CiviCRM API v3 (files inside api/v3/ are resolved via include_path)
  $api3Files = $gen->baseDir->search('find:api/v3/*.php');
  if (!empty($api3Files) || is_dir($gen->baseDir->string('api/v3'))) {
    $reasons[] = 'Extension defines CiviCRM API v3 files (api/v3/)';
  }

  // 2. Missing PSR-0 classloader for CRM_
  $crmDir = $gen->baseDir->string('CRM');
  $loaders = $info->getClassloaders();
  $hasPsr0 = in_array('CRM_', array_column($loaders, 'prefix'));
  if (is_dir($crmDir) && !$hasPsr0) {
    $reasons[] = 'Extension has CRM/ folder but lacks <classloader><psr0 prefix="CRM_"/> in info.xml';
  }

  // 3. Potential core class overrides or files in CRM/ outside the extension's declared namespace
  if (is_dir($crmDir)) {
    try {
      $ns = $info->getNamespace();
    }
    catch (\Exception $e) {
      $shortName = $info->getFile() ?: Naming::createShortName($info->getKey());
      $ns = 'CRM/' . Naming::createCamelName($shortName);
    }

    $expectedNsDir = $gen->baseDir->string(str_replace(['_', '\\'], '/', $ns));
    $overrideFiles = [];
    $crmIter = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($crmDir, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($crmIter as $file) {
      if ($file->getExtension() === 'php') {
        $filePath = $file->getPathname();
        // Check if file is outside the extension's namespace directory (case-insensitive)
        if (stripos($filePath, $expectedNsDir) !== 0) {
          $overrideFiles[] = Files::relativize($filePath, $gen->baseDir->string());
        }
      }
    }
    if (!empty($overrideFiles)) {
      $reasons[] = 'Extension has files in CRM/ outside its namespace (' . implode(', ', array_slice($overrideFiles, 0, 3)) . (count($overrideFiles) > 3 ? '...' : '') . ') which may override core classes';
    }
  }

  // 4. Legacy packages/ or lib/ directories
  if (is_dir($gen->baseDir->string('packages'))) {
    $reasons[] = 'Extension contains packages/ directory (may require include_path, depending on the packages)';
  }
  if (is_dir($gen->baseDir->string('lib'))) {
    $reasons[] = 'Extension contains lib/ directory (may require include_path, depending on the libraries)';
  }

  // 5. Bare relative require / include statements that might rely on include_path
  $relativeRequires = [];
  $allPhp = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($gen->baseDir->string(), RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
  );
  foreach ($allPhp as $file) {
    if ($file->getExtension() !== 'php') {
      continue;
    }
    $filePath = $file->getPathname();
    $relPath = Files::relativize($filePath, $gen->baseDir->string());

    // Skip vendor, node_modules, and civix generated files
    if (strpos($relPath, 'vendor/') === 0 || strpos($relPath, 'node_modules/') === 0) {
      continue;
    }
    if (substr($file->getFilename(), -10) === '.civix.php') {
      continue;
    }

    $content = @file_get_contents($filePath);
    if (!$content) {
      continue;
    }

    if (preg_match_all('/(require|include)(_once)?\s*[\(\s]*[\'"](CRM\/[^\'"]+|api\/v3\/[^\'"]+)[\'"]/', $content, $matches)) {
      foreach ($matches[3] as $target) {
        $relativeRequires[] = "$relPath ($target)";
      }
    }
  }
  if (!empty($relativeRequires)) {
    $reasons[] = 'Extension contains relative require/include statements: ' . implode(', ', array_slice($relativeRequires, 0, 3)) . (count($relativeRequires) > 3 ? '...' : '');
  }

  if (!empty($reasons)) {
    $io->section('PHP Include Path Mixin');
    $io->note([
      "Historically, civix added every extension to PHP's include_path, but it's more efficient not to.",
      'Now, extensions are NOT added unless they enable the "include-path" mixin.',
      'Civix has scanned this extension and recommends enabling it because:',

      implode("\n", array_map(function ($r) {
        return " - $r";
      }, $reasons)),
    ]);

    if ($io->confirm('Add the "include-path@1" mixin? (Recommended)', TRUE)) {
      $gen->addMixins(['include-path@1.0.0']);
      $io->success('Added "include-path@1" mixin.');
    }
    else {
      $io->note('Skipped adding the "include-path@1" mixin.');
    }
  }
  else {
    $io->section('PHP Include Path Optimization');
    $io->note([
      "Historically, civix added every extension to PHP's include_path, but it's more efficient not to.",
      'Now, extensions are NOT added unless they enable the "include-path" mixin.',
      'Civix has scanned this extension and found no code that appears to need it:',

      ($hasPsr0 ? " - PSR autoloader is in place\n" : " - No CRM/ directory\n") .
      " - No legacy api/v3/ directory\n" .
      " - No CRM core overrides\n" .
      " - No packages/ or lib/ directory\n" .
      " - No relative require or include statements",

      'It is recommended to omit this mixin unless this extension requires PHP include_path for some other reason.',
    ]);

    if ($io->confirm('Add the "include-path@1" mixin?', FALSE)) {
      $gen->addMixins(['include-path@1.0.0']);
      $io->success('Added "include-path@1" mixin.');
    }
    else {
      $io->note('Skipped adding the "include-path@1" mixin.');
    }
  }
};
