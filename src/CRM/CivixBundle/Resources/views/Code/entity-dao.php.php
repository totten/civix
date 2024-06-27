<?php
echo "<?php\n";
$_namespace = preg_replace(':/:', '_', $namespace);
$_clPrefix = 'CiviMix\\Schema\\' . \CRM\CivixBundle\Utils\Naming::createCamelName($mainFile) . '\\'

?>
use <?php echo $_namespace ?>_ExtensionUtil as E;

if (FALSE) {
  // Generate IDE hints that are fairly close to reality.
  class_alias('CRM_Core_DAO_Base', <?php var_export($_clPrefix . "DAO"); ?>);
}

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in several versions of CiviCRM (<5.75)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. However, you may add comments and annotations.
 */
class <?php echo $daoClassName ?> extends <?php echo $_clPrefix . "DAO"; ?> {

  // Required by some versions of CiviCRM.
  public static $_tableName = <?php var_export($tableName); ?>;

}
