<?php
echo '<?xml version="1.0" encoding="iso-8859-1" ?>'."\n";
$version = civicrm_api3('Extension', 'getvalue', ['key' => $mainFile, 'return' => 'version']);
?>

<table>
  <base><?php echo $namespace ?></base>
  <class><?php echo $entityNameCamel ?></class>
  <name><?php echo $tableName ?></name>
  <comment>FIXME</comment>
  <add><?php echo $version ?></add>
  <log>true</log>

  <field>
    <name>id</name>
    <type>int unsigned</type>
    <required>true</required>
    <comment>Unique <?php echo $entityNameCamel ?> ID</comment>
    <add><?php echo $version ?></add>
  </field>
  <primaryKey>
    <name>id</name>
    <autoincrement>true</autoincrement>
  </primaryKey>

  <field>
    <name>contact_id</name>
    <type>int unsigned</type>
    <comment>FK to Contact</comment>
    <add><?php echo $version ?></add>
  </field>
  <foreignKey>
    <name>contact_id</name>
    <table>civicrm_contact</table>
    <key>id</key>
    <add><?php echo $version ?></add>
    <onDelete>CASCADE</onDelete>
  </foreignKey>

</table>
