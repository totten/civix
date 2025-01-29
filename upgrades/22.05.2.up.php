<?php

use CRM\CivixBundle\Builder\Mixins;
use CRM\CivixBundle\Utils\EvilEx;

return function (\CRM\CivixBundle\Generator $gen) {
  $mixins = new Mixins($gen->infoXml, $gen->baseDir->string('mixin'));
  $declared = $mixins->getDeclaredMixinConstraints();
  $hasSettingMixin = (bool) preg_grep('/^setting-php@/', $declared);
  $action = NULL;

  $gen->updateModulePhp(function (\CRM\CivixBundle\Builder\Info $info, string $content) use ($gen, $hasSettingMixin, &$action) {
    $prefix = $gen->infoXml->getFile();
    $hookFunc = "{$prefix}_civicrm_alterSettingsFolders";
    $hookBody = [
      'static $configured = FALSE;',
      'if ($configured) return;',
      '$configured = TRUE;',
      '$extRoot = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;',
      '$extDir = $extRoot . \'settings\';',
      'if(!in_array($extDir, $metaDataFolders)){',
      '  $metaDataFolders[] = $extDir;',
      '}',
    ];

    $newContent = EvilEx::rewriteMultilineChunk($content, $hookBody, function(array $matchLines) use ($hookFunc, $content, $gen, $hasSettingMixin, &$action) {
      /* @var \Symfony\Component\Console\Style\OutputStyle $io */
      $io = \Civix::io();
      $matchLineKeys = array_keys($matchLines);
      $allLines = explode("\n", $content);
      $focusStart = min($matchLineKeys);
      $focusEnd = max($matchLineKeys);

      $io->note("The following chunk resembles an older template for \"{$hookFunc}()\".");
      $gen->showCode($allLines, $focusStart - 4, $focusEnd + 4, $focusStart, $focusEnd);

      if ($hasSettingMixin) {
        $io->note([
          "Similar functionality is now provided by the \"setting-php\" mixin, which is already enabled.",
        ]);
      }
      else {
        $io->note([
          "Similar functionality is now available in the \"setting-php\" mixin, which is currently disabled.",
        ]);
      }

      $actions = [
        'm' => 'Use the mixin ("setting-php") and remove this boilerplate.',
        'b' => 'Use the boilerplate and disable the mixin ("setting-php").',
        'n' => 'Do nothing. Keep as-is. (You may manually change it later.)',
      ];
      $action = $io->choice("What should we do?", $actions, 'm');
      return ($action == 'm') ? [] : $matchLines;
    });

    if ($action === 'm' && !$hasSettingMixin) {
      $gen->updateMixins(function (Mixins $mixins) {
        $mixins->addMixin('setting-php@1.0.0');
      });
    }
    elseif ($action === 'b' && $hasSettingMixin) {
      $gen->updateMixins(function (Mixins $mixins) {
        $mixins->removeMixin('setting-php');
      });
    }

    return $newContent;
  });

};
