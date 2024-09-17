<?php
echo "<" . "?php\n";
if ($classNamespaceDecl) {
  echo "$classNamespaceDecl\n\n";
}
echo "$useE\n";
?>

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in several versions of CiviCRM (<5.75)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. However, you may add comments and annotations.
 */
class <?php echo $className ?> extends <?php echo $daoBaseClass; ?> {

  // Required by some versions of CiviCRM.
  public static $_tableName = <?php var_export($tableName); ?>;

}
