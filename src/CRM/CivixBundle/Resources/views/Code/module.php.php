<?php
echo "<?php\n";
$_namespace = preg_replace(':/:', '_', $namespace);
?>

require_once '<?php echo $mainFile ?>.civix.php';
// phpcs:disable
use <?php echo $_namespace ?>_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function <?php echo $mainFile ?>_civicrm_config(&$config): void {
  _<?php echo $mainFile ?>_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function <?php echo $mainFile ?>_civicrm_install(): void {
  _<?php echo $mainFile ?>_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function <?php echo $mainFile ?>_civicrm_enable(): void {
  _<?php echo $mainFile ?>_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function <?php echo $mainFile ?>_civicrm_preProcess($formName, &$form): void {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function <?php echo $mainFile ?>_civicrm_navigationMenu(&$menu): void {
//  _<?php echo $mainFile ?>_civix_insert_navigation_menu($menu, 'Mailings', [
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ]);
//  _<?php echo $mainFile ?>_civix_navigationMenu($menu);
//}
