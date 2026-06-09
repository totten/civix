<?php

use CRM\CivixBundle\Generator;
use CRM\CivixBundle\Utils\Files;
use PhpArrayDocument\Printer;

/**
 * Upgrade managed/SavedSearch_*.mgd.php files to 6.15 (translated) or 6.16 (bare) standard.
 */
return function (Generator $gen) {
  $io = \Civix::io();
  $fieldsToCheck = ['title', 'title_plural', 'label', 'description'];
  // $fieldsToCheck = ['label'];

  $printNode = function (\PhpArrayDocument\BaseNode $node) {
    $printer = new \PhpArrayDocument\Printer();
    // $doc = new \PhpArrayDocument\PhpArrayDocument();
    $method = new ReflectionMethod($printer, 'printNode');
    $method->setAccessible(TRUE);
    return $method->invoke($printer, $node);
  };

  $findSavedSearchStrings = function (\PhpArrayDocument\BaseNode $root) use ($fieldsToCheck, $printNode): \Generator {
    yield from [];
    foreach ($fieldsToCheck as $fieldName) {
      $nodes = $root->findPath(['*', 'params', 'values', 'settings', 'columns', '*', $fieldName]);
      foreach ($nodes as $fieldNode) {
        if ($fieldNode instanceof \PhpArrayDocument\ScalarNode && $fieldNode->getScalar() !== '') {
          yield $fieldNode;
        }
      }
    }
  };

  $mgdFiles = $gen->baseDir->search('find:managed/SavedSearch_*.mgd.php');
  if (empty($mgdFiles)) {
    $io->note('No managed/SavedSearch_*.mgd.php files found. Skipping upgrade.');
    return;
  }

  $io->title('Translation support for Saved Searches');

  $reports = [];
  $expressionCount = 0;
  foreach ($mgdFiles as $mgdFile) {
    $relPath = Files::relativize($mgdFile, $gen->baseDir->string());
    /**
     * @var \PhpArrayDocument\PhpArrayDocument $document
     */
    $document = (new \PhpArrayDocument\Parser())->parse(file_get_contents($relPath));
    $expressions = [];
    foreach ($findSavedSearchStrings($document->getRoot()) as $stringNode) {
      $expressions[] = $printNode($stringNode);
    }
    $expressions = array_unique($expressions);
    sort($expressions);

    $report = ['File' => basename($relPath), 'Expressions' => implode("\n", $expressions)];
    $expressionCount += count($expressions);
    if (empty($report['Expressions'])) {
      $report['Expressions'] = '*NO STRINGS*';
    }
    $reports[] = $report;
  }
  if (empty($expressionCount)) {
    $io->note('No relevant strings found. Skipping upgrade.');
    return;
  }

  $io->note([
    'In CiviCRM v6.16, SearchKit can perform multilingual translation of Saved Searches (labels, descriptions, etc). This is useful for organizations that operate in multiple locales.',
    'To support multilingual, you should remove E::ts() and allow SearchKit to lookup the translations.',
    'However, removing E::ts() will disable translation on older versions of CiviCRM (<=v6.15).',
    sprintf('You have %d relevant strings.', $expressionCount),
  ]);

  if ($io->confirm('Would you like to review affected strings?')) {
    $io->table(array_keys($reports[0]), $reports);
  }

  if (Civix::checker()->coreVersionIs('>=', '6.16')) {
    $action = $io->confirm('Continue to remove old E::ts() from relevant strings?') ? 'r' : 'n';
  }
  else {
    $action = $io->choice('How you would like to handle the migration?', [
      'r' => 'Require CiviCRM v6.16+. Remove E::ts() from strings. Block older versions of CiviCRM.',
      'p' => 'Prefer CiviCRM v6.16+. Remove E::ts() from strings. Older versions of CiviCRM are allowed, but translation may not work.',
      'n' => 'No change. Leave the current strings and current CiviCRM requirements.',
    ], 'p');
  }

  if ($action === 'r') {
    $gen->updateInfo(function(\CRM\CivixBundle\Builder\Info $info) {
      $info->raiseCompatibilityMinimum('6.16');
    });
  }
  if ($action === 'r' || $action === 'p') {
    foreach ($mgdFiles as $mgdFile) {
      $relPath = Files::relativize($mgdFile, $gen->baseDir->string());
      $io->section("Update strings in $relPath");
      /**
       * @var \PhpArrayDocument\PhpArrayDocument $doc
       */
      $doc = (new \PhpArrayDocument\Parser())->parse(file_get_contents($mgdFile));

      foreach ($findSavedSearchStrings($doc->getRoot()) as $stringNode) {
        $stringNode->setFactory(NULL);
      }

      $content = (new Printer())->print($doc);
      file_put_contents($mgdFile, $content);
    }
  }
  if ($action === 'n') {
    $io->note([
      'No changes will be made.',
      "If you wish run this in the future, call: civix upgrade --start=26.0.0",
    ]);
  }

};
