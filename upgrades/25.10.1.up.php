<?php

/**
 * Recommend scan-classes (in general).
 */
return function (\CRM\CivixBundle\Generator $gen) {
  $checker = \Civix::checker();
  if ($checker->hasMixin('/^scan-classes@/')) {
    return;
  }
  $hookScanClasses = $gen->infoXml->getFile() . '_civicrm_scanClasses';
  if (in_array($hookScanClasses, $checker->getMyGlobalFunctions())) {
    return;
  }

  \Civix::io()->note([
    "In CiviCRM v5.51+, class-scanning empowers you to write more modular code.",
    "We suggest enabling the standard scanner (\"scan-classes@1\") for most extensions.",
    "In some cases, you may omit scanning or use a custom scanner. Review this table for relevant notices:",
  ]);

  $indicators = [];
  $addIndicator = function ($level, $name, $messages = []) use (&$indicators) {
    $messages = implode("\n", (array) $messages);
    $indicators[$name] = ["<info>$level</info>", "<comment>$name</comment>" . ($messages ? ": $messages" : '')];
  };

  $default = 's';

  if (!file_exists(Civix::extDir('Civi/Api4'))) {
    $addIndicator('OK', 'Civi\Api4', "Not applicable");
  }
  else {
    $addIndicator('IMPORTANT', 'Civi\Api4', "You must enable scanning to ensure future compatibility.\n(See https://github.com/civicrm/civicrm-core/pull/33371)");
  }

  if ($checker->coreVersionIs('>=', '5.51')) {
    $addIndicator('OK', 'Core Compatibility', sprintf('You already require CiviCRM <comment>v%s+</comment>.', $gen->infoXml->getCompatibilityVer()));
  }
  else {
    $addIndicator('IMPORTANT', 'Core Compatibility', 'Scanner requires CiviCRM v5.51+.');
  }

  // These are some cases where people have undeclared dependencies.
  $extClasses = [
    ['Legacy Custom Search', 'legacycustomsearches', 'CRM_Contact_Form_Search_Custom_Base'],
    ['CiviRules', 'org.civicoop.civirules', 'CRM_Civirules'],
  ];
  foreach ($extClasses as $extClass) {
    [$extTitle, $extKey, $baseClassPattern] = $extClass;
    if ($gen->infoXml->getKey() !== $extKey && !$checker->hasRequirement($extKey) && $checker->hasSubclassesOf($baseClassPattern)) {
      $default = 'c';
    }
  }

  if (file_exists(Civix::extDir('CRM')) || file_exists(Civix::extDir('Civi'))) {
    $addIndicator('REVIEW', 'Optional Dependencies', [
      'If you have any, then use the <comment>custom scanner</comment>.',
      'An "optional dependency" is another extension that you connect with -- but do not require.',
      'For example, your extension could provide an optional CiviRules trigger to benefit site-builders,',
      'even though the extension does not require CiviRules for itself. Any optional dependencies',
      'may lead to errors in the standard scanner. To avoid this, use a <comment>custom scanner</comment>.',
    ]);
  }

  ksort($indicators);
  $n = 0;
  foreach ($indicators as &$indicator) {
    array_unshift($indicator, ++$n);
  }
  \Civix::io()->table(['#', 'Severity', 'Check'], $indicators);

  $choice = \Civix::io()->choice('What kind of class-scanner should we use?', [
    's' => 'Standard scanner (scan-classes@1)',
    'c' => 'Custom scanner (hook_civicrm_scanClasses)',
    'n' => 'None (Disable scanning)',
    'a' => 'Abort',
  ], $default);

  switch ($choice) {
    case 's':
      $gen->addMixins(['scan-classes@1.0']);
      break;

    case 'c':
      $gen->updateModulePhp(function (\CRM\CivixBundle\Builder\Info $info, string $content) use ($hookScanClasses): string {
        return implode("\n", [
          $content,
          '',
          '/**',
          ' * Implements hook_civicrm_scanClasses',
          ' *',
          ' * @see CRM_Utils_Hook::scanClasses()',
          ' */',
          'function ' . $hookScanClasses . '(array &$classes) {',
          '  // Example 1: Declare the exact classes that should be scanned.',
          '  // $classes[] = "CRM_Example_Class";',
          '',
          '  // Example 2: Scan specific subfolder(s)',
          '  \Civi\Core\ClassScanner::scanFolders($classes, __DIR__, \'Civi/Api4\', \'\\\\\');',
          '  // \Civi\Core\ClassScanner::scanFolders($classes, __DIR__, \'Civi/Foobar\', \'\\\\\');',
          '  // \Civi\Core\ClassScanner::scanFolders($classes, __DIR__, \'CRM/Foobar\', \'_\');',
          '',
          '  // Example 3: Scan specific folder(s), with exclusions',
          '  // \Civi\Core\ClassScanner::scanFolders($classes, __DIR__, \'Civi\', \'\\\\\', \';...regex...;\');',
          '}',
        ]);
      });
      break;

    case 'n':
      break;

    case 'a':
      throw new \RuntimeException('User stopped upgrade');
  }

};
