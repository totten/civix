<?php
echo "<?php\n";
?>

require_once '<?php echo $mainFile ?>.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function <?php echo $mainFile ?>_civicrm_config(&$config) {
  _<?php echo $mainFile ?>_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function <?php echo $mainFile ?>_civicrm_xmlMenu(&$files) {
  _<?php echo $mainFile ?>_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function <?php echo $mainFile ?>_civicrm_install() {
  return _<?php echo $mainFile ?>_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function <?php echo $mainFile ?>_civicrm_uninstall() {
  return _<?php echo $mainFile ?>_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function <?php echo $mainFile ?>_civicrm_enable() {
  return _<?php echo $mainFile ?>_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function <?php echo $mainFile ?>_civicrm_disable() {
  return _<?php echo $mainFile ?>_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function <?php echo $mainFile ?>_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _<?php echo $mainFile ?>_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function <?php echo $mainFile ?>_civicrm_managed(&$entities) {
  return _<?php echo $mainFile ?>_civix_civicrm_managed($entities);
}
