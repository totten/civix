<?php
echo "<" . "?php\n";
if ($classNamespaceDecl) {
  echo "$classNamespaceDecl\n\n";
}
?>

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in older versions of CiviCRM (<5.74)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. Property annotations may be added, but are not required.
<?php
foreach ($properties as $propName => $propType) {
  echo " * @property $propType \$$propName\n";
}
?> */
class <?php echo $className ?> extends <?php echo $daoBaseClass; ?> {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = <?php var_export($tableName); ?>;

}
