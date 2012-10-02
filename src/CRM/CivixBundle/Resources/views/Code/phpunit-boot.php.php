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

global $civicrm_setting;
$civix_civicrm_setting = <?php var_export($civicrm_setting); ?>;
$civicrm_setting = _civix_phpunit_settings_merge($civix_civicrm_setting, $civicrm_setting);
