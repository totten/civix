<?php
echo "<?php\n";
?>

// AUTO-GENERATED FILE -- This may be overwritten!

/**
 * (Delegated) Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function _<?= $mainFile ?>_civix_xmlMenu(&$files) {
  foreach (glob(__DIR__ . '/xml/Menu/*.xml') as $file) {
    $files[] = $file;
  }
}
