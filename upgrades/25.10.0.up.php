<?php

/**
 * Look for common problems with undeclared dependencies.
 */
return function (\CRM\CivixBundle\Generator $gen) {
  $checker = \Civix::checker();

  $extClasses = [
    ['Legacy Custom Search', 'legacycustomsearches', ';CRM_Contact_Form_Search_Custom_Base;i'],
    ['CiviRules', 'org.civicoop.civirules', ';CRM_Civirules;i'],
  ];
  foreach ($extClasses as $extClass) {
    [$extTitle, $extKey, $baseClassPattern] = $extClass;

    \Civix::io()->section("Dependency check: $extTitle ($extKey)");

    if ($gen->infoXml->getKey() === $extKey) {
      continue;
    }
    if ($checker->hasRequirement($extKey)) {
      Civix::io()->writeln("<info>[OK]</info> Already declares requirement for \"$extKey\".");
      continue;
    }

    $matches = $checker->grep($baseClassPattern, ['CRM', 'Civi'], '*.php');
    if (empty($matches)) {
      Civix::io()->writeln("<info>[OK]</info> No evidence of hidden dependency.");
      continue;
    }
    $relMatches = array_map(fn($f) => \CRM\CivixBundle\Utils\Files::relativize($f, Civix::extDir()), $matches);

    Civix::io()->warning([
      "The following files have markers which appear related to \"$extKey\":",
      \CRM\CivixBundle\Utils\Formatting::ol("%s\n", $relMatches),
      "However, \"info.xml\" does NOT declare a formal dependency on \"$extKey\".",
    ]);

    Civix::io()->note([
      "Why would this be?",
      "It's possible that \"$extKey\" is truly REQUIRED -- and it SHOULD be listed in \"info.xml\".",
      "It's possible that \"$extKey\" is truly OPTIONAL -- and it should NOT be listed in \"info.xml\".",
      "This is a subjective question which civix cannot solve automatically. We need you to do decide.",
    ]);

    // Prefer to have no default - but for purposes of automated tests, we'll assume that existing code is accurate.
    $default = Civix::input()->isInteractive() ? NULL : 'o';
    $choice = \Civix::io()->choice("Is the extension \"$extKey\" required or optional?", [
      'r' => 'Required',
      'o' => 'Optional',
      'a' => 'Abort',
    ], $default);

    switch ($choice) {
      case 'r':
        $gen->updateInfo(function (\CRM\CivixBundle\Builder\Info $info) use ($extKey) {
          $requires = $info->getRequiredExtensions();
          $requires[] = $extKey;
          $info->setRequiredExtensions($requires);
        });
        break;

      case 'o':
        // Leave it alone...
        // Might be nice to record this decision somewhere, but I'm not sure where.
        break;

      case 'a':
        throw new \RuntimeException('User stopped upgrade');
    }
  }

};
