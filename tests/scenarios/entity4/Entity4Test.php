<?php

use CRM_Civixsnapshot_ExtensionUtil as E;

/**
 * @group e2e
 */
class Entity4Test extends \PHPUnit\Framework\TestCase {

  public function setUp(): void {
    parent::setUp();
    $mapper = \CRM_Extension_System::singleton()->getMapper();
    $this->assertTrue($mapper->isActiveModule('civixsnapshot'), 'Extension civixsnapshot should already be active.');
  }

  public function testSchemaExists() {
    $table = 'civicrm_my_entity_four';
    $exists = \CRM_Core_DAO::checkTableExists($table);
    $this->assertTrue($exists, "Table $table should exist");
  }

  public function testAlterSchemaField() {
    $this->assertEquals(FALSE, CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_my_entity_four', 'extra_string'), 'extra_string should not yet exist');
    E::schema()->alterSchemaField('MyEntityFour', 'extra_string', [
      'title' => ts('Example'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => FALSE,
    ]);
    $this->assertEquals(TRUE, CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_my_entity_four', 'extra_string'), 'extra_string should exist');
  }

}
