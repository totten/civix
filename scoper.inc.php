<?php declare(strict_types = 1);

return [
  'prefix' => 'CivixPhar',

  'exclude-namespaces' => [
    // Provided by civicrm
    'Civi',
    'Guzzle',
    'Symfony\Component\DependencyInjection',

    // Drupal8+ bootstrap
    'Drupal',
    'Symfony\\Component\\HttpFoundation',
    'Symfony\\Component\\Routing',

    // Joomla bootstrap
    'TYPO3\\PharStreamWrapper',
  ],

  'exclude-classes' => [
    '/^(CRM_|HTML_|DB_|Log_)/',
    'civicrm_api3',
    'DB',
    'Log',
    'JFactory',
    'Civi',
    'Drupal',
  ],
  'exclude-functions' => [
    '/^civicrm_/',
    '/^wp_.*/',
    '/^(drupal|backdrop|user|module)_/',
    't',
  ],

  // Do not generate wrappers/aliases for `civicrm_api()` etc or various CMS-booting functions.
  'expose-global-functions' => FALSE,

  // Do not filter template files
  'exclude-files' => array_merge(
    glob('src/CRM/CivixBundle/Resources/views/*/*.php'),
    glob('extern/*/*/*.php'),
    glob('extern/*/*.php'),
  )

];
