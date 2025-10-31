<?php
echo "<?php\n";
echo "declare(strict_types = 1);\n";
$_namespace = preg_replace(':/:', '_', $namespace);
?>

// phpcs:disable PSR1.Files.SideEffects
require_once '<?php echo $mainFile ?>.civix.php';
// phpcs:enable

use <?php echo $_namespace ?>_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function <?php echo $mainFile ?>_civicrm_config(\CRM_Core_Config $config): void {
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
