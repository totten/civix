<?php

use CRM\CivixBundle\Generator;
use CRM\CivixBundle\Utils\Files;
use PhpArrayDocument\ArrayNode;
use PhpArrayDocument\BaseNode;
use PhpArrayDocument\ParseException;
use PhpArrayDocument\Parser;
use PhpArrayDocument\Printer;
use PhpArrayDocument\ScalarNode;

/**
 * Remove E::ts() from the texts of managed Search Displays.
 *
 * SearchDisplay::get translates these texts when they are read, with the managing extension's translations: labels
 * from v6.16 and every user-facing text from v6.22. E::ts() in a managed file translates once, when the record is
 * saved, into whatever locale is active, which defeats that. See https://github.com/civicrm/civicrm-core/pull/37639
 */
return function (Generator $gen) {
  $io = \Civix::io();
  $coreVersion = '6.22';

  // The texts SearchDisplay::get translates, as paths below a display's 'values' (or below one of its columns).
  $displayPaths = [
    ['label'],
    ['settings', 'description'],
    ['settings', 'noResultsText'],
    ['settings', 'button'],
    ['settings', 'tally', 'label'],
    ['settings', 'editableRow', 'createLabel'],
    ['settings', 'addButton', 'text'],
    ['settings', 'toolbar', '*', 'text'],
    ['settings', 'toolbar', '*', 'title'],
  ];
  $columnPaths = [
    ['label'],
    ['title'],
    ['text'],
    ['rewrite'],
    ['empty_value'],
    ['image', 'alt'],
    ['tally', 'label'],
    ['links', '*', 'text'],
    ['links', '*', 'title'],
  ];

  $isTranslatedText = function ($node): bool {
    if (!($node instanceof ScalarNode) || !in_array($node->getFactory(), ['E::ts', 'ts'], TRUE)) {
      return FALSE;
    }
    $text = $node->getScalar();
    // SearchDisplay::get leaves markup and Smarty alone, so those still need E::ts()
    return is_string($text) && $text !== ''
      && !(str_contains($text, '<') && str_contains($text, '>'))
      && !(str_contains($text, '{') && str_contains($text, '}'));
  };

  $findTranslatedTexts = function (ArrayNode $root) use ($displayPaths, $columnPaths, $isTranslatedText): \Generator {
    yield from [];
    foreach ($root->findPath(['*']) as $record) {
      $entity = $record instanceof ArrayNode ? $record['entity'] : NULL;
      if (!($entity instanceof ScalarNode) || $entity->getScalar() !== 'SearchDisplay') {
        continue;
      }
      foreach ($record->findPath(['params', 'values']) as $values) {
        foreach ($displayPaths as $path) {
          foreach ($values->findPath($path) as $node) {
            if ($isTranslatedText($node)) {
              yield $node;
            }
          }
        }
        foreach ($values->findPath(['settings', 'columns', '*']) as $column) {
          $type = $column instanceof ArrayNode ? $column['type'] : NULL;
          $isHtml = $type instanceof ScalarNode && $type->getScalar() === 'html';
          foreach ($columnPaths as $path) {
            // The rewrite of an html column is markup, not a text
            if ($isHtml && $path === ['rewrite']) {
              continue;
            }
            foreach ($column->findPath($path) as $node) {
              if ($isTranslatedText($node)) {
                yield $node;
              }
            }
          }
        }
      }
    }
  };

  // Render a node as PHP code. Ex: "E::ts('Name')"
  $printNode = function (BaseNode $node): string {
    $method = new \ReflectionMethod(Printer::class, 'printNode');
    $method->setAccessible(TRUE);
    return $method->invoke(new Printer(), $node);
  };

  $mgdFiles = $gen->baseDir->search('find:managed/SavedSearch_*.mgd.php');
  if (empty($mgdFiles)) {
    $io->note('No managed/SavedSearch_*.mgd.php files found. Skipping upgrade.');
    return;
  }

  $io->title('Translation support for Search Displays');

  $reports = [];
  $expressionCount = 0;
  $parseableDocs = [];
  foreach ($mgdFiles as $mgdFile) {
    $relPath = Files::relativize($mgdFile, $gen->baseDir->string());
    try {
      $document = (new Parser())->parse(file_get_contents($mgdFile));
    }
    catch (ParseException $e) {
      $io->warning(sprintf('Skipping %s: unable to parse (%s)', $relPath, $e->getMessage()));
      continue;
    }
    $parseableDocs[$mgdFile] = $document;
    $expressions = [];
    foreach ($findTranslatedTexts($document->getRoot()) as $stringNode) {
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
    'SearchKit translates the texts of a Search Display (labels, descriptions, tooltips, empty values, buttons, etc) for each viewer: labels from CiviCRM v6.16, every text from v6.22.',
    'E::ts() in a managed file translates once, when the record is saved, into the locale in use at the time. To let SearchKit translate for each viewer, remove E::ts().',
    'However, removing E::ts() will disable translation of these strings on older versions of CiviCRM (labels on <=v6.15, other texts on <=v6.21).',
    sprintf('You have %d relevant strings.', $expressionCount),
  ]);

  if ($io->confirm('Would you like to review affected strings?')) {
    $io->table(array_keys($reports[0]), $reports);
  }

  if (\Civix::checker()->coreVersionIs('>=', $coreVersion)) {
    $action = $io->confirm('Continue to remove old E::ts() from relevant strings?') ? 'r' : 'n';
  }
  else {
    $action = $io->choice('How you would like to handle the migration?', [
      'r' => "Require CiviCRM v$coreVersion+. Remove E::ts() from strings. Block older versions of CiviCRM.",
      'p' => "Prefer CiviCRM v$coreVersion+. Remove E::ts() from strings. Older versions of CiviCRM are allowed, but translation may not work.",
      'n' => 'No change. Leave the current strings and current CiviCRM requirements.',
    ], 'p');
  }

  if ($action === 'r') {
    $gen->updateInfo(function(\CRM\CivixBundle\Builder\Info $info) use ($coreVersion) {
      $info->raiseCompatibilityMinimum($coreVersion);
    });
  }
  if ($action === 'r' || $action === 'p') {
    foreach ($parseableDocs as $mgdFile => $doc) {
      $relPath = Files::relativize($mgdFile, $gen->baseDir->string());
      $io->section("Update strings in $relPath");

      foreach ($findTranslatedTexts($doc->getRoot()) as $stringNode) {
        $stringNode->setFactory(NULL);
      }

      file_put_contents($mgdFile, (new Printer())->print($doc));
    }
  }
  if ($action === 'n') {
    $io->note([
      'No changes will be made.',
      'If you wish to run this in the future, call: civix upgrade --start=26.10.3',
    ]);
  }
};
