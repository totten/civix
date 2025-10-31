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
    'Joomla\\',
  ],

  'exclude-classes' => [
    '/^(CRM_|HTML_|DB_|Log_)/',
    '/^PEAR_(Error|Exception)/',
    'civicrm_api3',
    'Mixlib',
    'DB',
    'Log',
    'JFactory',
    'Civi',
    'Drupal',
    'Joomla',
  ],
  'exclude-functions' => [
    '/^civicrm_/',
    '/_civicrm_api_get_entity_name_from_camel/',
    '/^wp_.*/',
    '/^(drupal|backdrop|user|module)_/',
    't',
  ],

  // Do not generate wrappers/aliases for `civicrm_api()` etc or various CMS-booting functions.
  'expose-global-functions' => FALSE,

  // Do not filter template files
  'exclude-files' => array_merge(
    glob('lib/*.phar'),
    glob('lib/*.php'),
    glob('src/CRM/CivixBundle/Resources/views/*/*.php'),
    glob('extern/*/*/*.php'),
    glob('extern/*/*.php'),
    glob('vendor/symfony/polyfill-php80/Resources/stubs/*php'), /* polyfill-php80@1.27.0 + box@4.8.3 */
  ),
  'exclude-constants' => [
    'JPATH_BASE',
    'JPATH_LIBRARIES',
    'CIVICRM_SETTINGS_PATH',
    'DS',
    '_JEXEC',
  ],

];
