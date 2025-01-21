<?php

use CRM\CivixBundle\Parse\PrimitiveFunctionVisitor;

/**
 * If you have EFv2, then you probably don't want to implement hook_entityTypes directly.
 *
 * - After running 24.09.1, it creates '*.entityType.php' files and enables
 *   entity-type-php@. You shouldn't need a manual declaration.
 * - Most existing implementations of hook_entityTypes do not provide all
 *   the properties needed for EFv2.
 * - Registering twice (with incomplete data) is likely to cause trouble.
 *
 * This step will search for `hook_entityTypes` and warn if it's doing any non-trivial work.
 * It prompts you to (c)omment, (k)eep, or (d)elete the function.
 */
return function (\CRM\CivixBundle\Generator $gen) {

  if (!\Civix::checker()->hasSchemaPhp() && !\Civix::checker()->hasMixin('/^entity-types-php@2/')) {
    // OK, not our problem.
    return;
  }

  $gen->updateModulePhp(function (\CRM\CivixBundle\Builder\Info $infoXml, string $body) {
    $hookFunc = $infoXml->getFile() . '_civicrm_entityTypes';
    $hookDelegate = '_' . $infoXml->getFile() . '_civix_civicrm_entityTypes';

    /**
     * @param string $func
     *   Ex: 'myfile_civicrm_fooBar'
     * @param string $sig
     *   Ex: 'array &$arg1, string $arg2'
     * @param string $code
     *   Ex: 'echo "Hello";\necho "World";'
     */
    return PrimitiveFunctionVisitor::visit($body, function (string &$func, string &$sig, string &$code) use ($infoXml, $hookFunc, $hookDelegate) {
      if ($func !== $hookFunc || empty($code)) {
        return NULL;
      }

      $lines = preg_split(';\n\w*;', $code);
      $lines = array_map('trim', $lines);
      $lines = array_filter($lines, function ($line) {
        return !empty($line) && !preg_match(';^(#|//);', $line);
      });
      $delegateRE = '/' . preg_quote($hookDelegate, '/') . '/i';

      // $hasDelegate = !empty(preg_grep($delegateRE, $lines));
      $hasBespokeLogic = !empty(preg_grep($delegateRE, $lines, PREG_GREP_INVERT));

      if (!$hasBespokeLogic) {
        return NULL;
      }

      $io = Civix::io();
      $io->title("Suspicious Entity Hook");

      $fullCode = sprintf("function %s(%s) {%s}", $func, $sig, $code);
      Civix::generator()->showCode(explode("\n", $fullCode));

      $io->note([
        "In Entity Framework v2, conventions for metadata have changed.",
        "Metadata is usually loaded from schema/*.entityType.php.",
        "Using hook_civicrm_entityTypes to declare entities may create conflicts.",
      ]);

      $actionLabels = [
        'c' => 'Comment-out this function',
        'k' => 'Keep this function',
        'd' => 'Delete this function',
      ];
      $action = $io->choice("What should we do with {$func}()?", $actionLabels, 'c');
      $results = ['k' => NULL, 'c' => 'COMMENT', 'd' => 'DELETE'];
      return $results[$action] ?? NULL;
    });
  });

};
