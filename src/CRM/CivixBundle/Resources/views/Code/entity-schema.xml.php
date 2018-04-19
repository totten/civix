<?php
echo '<?xml version="1.0" encoding="iso-8859-1" ?>'."\n";
?>

<table>
  <base><?php echo $namespace ?></base>
  <class><?php echo $entityNameCamel ?></class>
  <name><?php echo $tableName ?></name>
  <comment>FIXME</comment>
  <log>true</log>

  <field>
    <name>id</name>
    <type>int unsigned</type>
    <required>true</required>
    <comment>Unique <?php echo $entityNameCamel ?> ID</comment>
  </field>
  <primaryKey>
    <name>id</name>
    <autoincrement>true</autoincrement>
  </primaryKey>

  <field>
    <name>contact_id</name>
    <type>int unsigned</type>
    <comment>FK to Contact</comment>
  </field>
  <foreignKey>
    <name>contact_id</name>
    <table>civicrm_contact</table>
    <key>id</key>
    <onDelete>CASCADE</onDelete>
  </foreignKey>

</table>
