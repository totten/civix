<?php
echo "<?php\n";
$_namespace = preg_replace(':/:', '_', $namespace);
?>
use <?php echo $_namespace ?>_ExtensionUtil as E;

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in several versions of CiviCRM (<5.75)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. However, you may add comments and annotations.
 */
class <?php echo $daoClassName ?> extends CRM_Core_DAO_Base {

}
