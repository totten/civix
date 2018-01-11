<?php
echo "<?php\n";
?>

// Note: $b overrides $a
function _civix_phpunit_settings_merge($a, $b) {
  $result = $a;
  $b = (array) $b;
  foreach ($b as $k1 => $v1) {
    foreach ($v1 as $k2 => $v2) {
      $result[$k1][$k2] = $v2;
    }
  }
  return $result;
}

/**
 * Install extensions on test database
 */
function _civix_phpunit_setUp() {
  static $init = FALSE;
  if ($init) {
    return;
  }
  $init = TRUE;

  global $civicrm_setting;
  $apiResult = civicrm_api('Extension', 'install', [
    'version' => 3,
    'keys' => $civicrm_setting['Test']['test_extensions'],
  ]);
  register_shutdown_function('_civix_phpunit_tearDown');
  if ($apiResult['is_error'] != 0) {
    throw new Exception("Failed to pre-install extensions: " . $apiResult['error_message']);
  }
}

/**
 * Uninstall extensions on test database
 */
function _civix_phpunit_tearDown() {
  global $civicrm_setting;
  $result = civicrm_api('Extension', 'disable', [
    'version' => 3,
    'keys' => $civicrm_setting['Test']['test_extensions'],
  ]);
  $result = civicrm_api('Extension', 'uninstall', [
    'version' => 3,
    'keys' => $civicrm_setting['Test']['test_extensions'],
    'removeFiles' => FALSE,
  ]);
}

global $civicrm_setting;
$civix_civicrm_setting = <?php var_export($civicrm_setting); ?>;
$civicrm_setting = _civix_phpunit_settings_merge($civix_civicrm_setting, $civicrm_setting);
