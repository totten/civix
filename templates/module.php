<?php
echo "<?php\n";
?>

require_once '<?= $mainFile ?>.civix.php';

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function <?= $mainFile ?>_xmlMenu(&$files) {
  _<?= $mainFile ?>_civix_xmlMenu($files);
}
