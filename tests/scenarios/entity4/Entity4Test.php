<?php

/**
 * @group e2e
 */
class Entity4Test extends \PHPUnit\Framework\TestCase {

  public function testSchemaExists() {
    $table = 'civicrm_my_entity_four';
    $exists = \CRM_Core_DAO::checkTableExists($table);
    $this->assertTrue($exists, "Table $table should exist");
  }

  // TODO
  // public function testAlterSchemaField() {
  //   $this->assertEquals(FALSE, CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_my_entity_four', 'extra_string'), 'extra_string should not yet exist');
  //   CRM_Civixsnapshot_ExtensionUtil::schema()->alterSchemaField('MyEntityFour', 'extra_string', [
  //     'type' => 'VARCHAR(64)',
  //   ]);
  //   $this->assertEquals(TRUE, CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_my_entity_four', 'extra_string'), 'extra_string should exist');
  // }

}
