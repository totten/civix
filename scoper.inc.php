<?php declare(strict_types = 1);

return [
  'prefix' => 'CivixPhar',
  'patchers' => [
      function (string $filePath, string $prefix, string $content) {
          // In some cases, `civix` references classes provided by civicrm-core or by the UF. Preserve the original names.
          $content = preg_replace(';CivixPhar\\\(CRM_|HTML_|DB_|Drupal|JFactory|Civi::);', '$1', $content);
          $content = preg_replace_callback(';CivixPhar\Civi\([A-Za-z0-9_\\]*);', function($m){
            if (substr($m[1], 0, 3) === 'Cv\\') return $m[0]; // Civi\Cv is mapped.
            else return 'Civi\\' . $m[1]; // Nothing else is mapped.
          }, $content);
          return $content;
      },
  ],

  // Do not generate wrappers/aliases for `civicrm_api()` etc or various CMS-booting functions.
  'whitelist-global-functions' => false,

  // Do not filter template files
  'files-whitelist' => glob('src/CRM/CivixBundle/Resources/views/*/*.php'),
];
