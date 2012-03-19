<?php
echo "<?php\n";
?>

require_once '<?= $mainFile ?>.civix.php';

/**
 * (Delegated) Implementation of hook_civicrm_config
 */
function <?= $mainFile ?>_civicrm_config(&$config) {
  _<?= $mainFile ?>_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function <?= $mainFile ?>_civicrm_xmlMenu(&$files) {
  _<?= $mainFile ?>_civix_civicrm_xmlMenu($files);
}
