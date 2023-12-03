<?php

namespace CRM\CivixBundle\Utils;

class NamingTest extends \PHPUnit\Framework\TestCase {

  public function getFullNameExamples() {
    return [
      // [$name, $expectValid, $expectShort, $expectCamel]
      ['org.example.foo', TRUE, 'foo', 'Foo'],
      ['org.example.foo-bar-whiz', TRUE, 'foo_bar_whiz', 'FooBarWhiz'],
      ['.org.example.foo', FALSE, NULL, NULL],
      ['civicrm-foo-bar', TRUE, 'civicrm_foo_bar', 'CivicrmFooBar'],
      ['foo-bar', TRUE, 'foo_bar', 'FooBar'],
      ['foo--bar', FALSE, NULL, NULL],
      ['foo-2-foo', TRUE, 'foo_2_foo', 'Foo2Foo'],
      ['-foo', FALSE, NULL, NULL],
      ['foo-', FALSE, NULL, NULL],
      ['foo.', FALSE, NULL, NULL],
      ['org..foobar', FALSE, NULL, NULL],
      ['org.example.foo--bar', FALSE, NULL, NULL],
    ];
  }

  /**
   * @dataProvider getFullNameExamples
   */
  public function testIsValidFullName($name, $expectValid, $expectShort, $expectCamel) {
    $this->assertEquals($expectValid, Naming::isValidFullName($name));
  }

  /**
   * @dataProvider getFullNameExamples
   */
  public function testCreateShortName($name, $expectValid, $expectShort, $expectCamel) {
    if (!$expectValid) {
      $this->assertTrue(TRUE);
      return;
    }
    $this->assertEquals($expectShort, Naming::createShortName($name));
  }

  /**
   * @dataProvider getFullNameExamples
   */
  public function testCreateCamelName($name, $expectValid, $expectShort, $expectCamel) {
    if (!$expectValid) {
      $this->assertTrue(TRUE);
      return;
    }
    $this->assertEquals($expectCamel, Naming::createCamelName($name));
  }

  public function getTableNameExamples() {
    return [
      // [$inputEntityName, $expectTableName]
      ['Foo', 'civicrm_foo'],
      ['Foobar', 'civicrm_foobar'],
      ['FooBar', 'civicrm_foo_bar'],
      ['FooBar2', 'civicrm_foo_bar2'],
      ['Foo2Bar', 'civicrm_foo2_bar'],
    ];
  }

  /**
   * @dataProvider getTableNameExamples
   */
  public function testCreateTableName($inputEntityName, $expectTableName) {
    $this->assertEquals($expectTableName, Naming::createTableName($inputEntityName));
  }

  public function testCreateClassName() {
    $this->assertEquals('CRM_Foobar_Upgrade', Naming::createClassName('CRM/Foobar', 'Upgrade'));
    $this->assertEquals('CRM_Foobar_Upgrade_Base', Naming::createClassName('CRM/Foobar', 'Upgrade', 'Base'));
    $this->assertEquals('CRM_Foobar_Upgrade_Base', Naming::createClassName('CRM/Foobar', ['Upgrade', 'Base']));

    $this->assertEquals('Civi\Foobar\Upgrade', Naming::createClassName('Civi/Foobar', 'Upgrade'));
    $this->assertEquals('Civi\Foobar\Upgrade\Base', Naming::createClassName('Civi/Foobar', 'Upgrade', 'Base'));
    $this->assertEquals('Civi\Foobar\Upgrade\Base', Naming::createClassName('Civi/Foobar', ['Upgrade', 'Base']));
  }

  public function testCreateClassFile() {
    $this->assertEquals('CRM/Foobar/Upgrade.php', Naming::createClassFile('CRM/Foobar', 'Upgrade'));
    $this->assertEquals('CRM/Foobar/Upgrade/Base.php', Naming::createClassFile('CRM/Foobar', 'Upgrade', 'Base'));
    $this->assertEquals('CRM/Foobar/Upgrade/Base.php', Naming::createClassFile('CRM/Foobar', ['Upgrade', 'Base']));

    $this->assertEquals('Civi/Foobar/Upgrade.php', Naming::createClassFile('Civi/Foobar', 'Upgrade'));
    $this->assertEquals('Civi/Foobar/Upgrade/Base.php', Naming::createClassFile('Civi/Foobar', 'Upgrade', 'Base'));
    $this->assertEquals('Civi/Foobar/Upgrade/Base.php', Naming::createClassFile('Civi/Foobar', ['Upgrade', 'Base']));
  }

  public function testCreateService() {
    $this->assertEquals('foo.bar', Naming::createServiceName('CRM/Foo', 'Bar'));
    $this->assertEquals('foo.bar', Naming::createServiceName('Civi/Foo', 'Bar'));
    $this->assertEquals('foo.bar', Naming::createServiceName('CRM_Foo', 'Bar'));
    $this->assertEquals('foo.bar', Naming::createServiceName('Civi\\Foo', 'Bar'));

    $this->assertEquals('fooBar.whizBang', Naming::createServiceName('CRM_FooBar', 'WhizBang'));
    $this->assertEquals('fooBar.whizBang', Naming::createServiceName('CRM/FooBar', 'WhizBang'));
    $this->assertEquals('fooBar.whizBang', Naming::createServiceName('Civi\\FooBar', 'WhizBang'));
    $this->assertEquals('fooBar.whizBang', Naming::createServiceName('Civi/FooBar', 'WhizBang'));

    $this->assertEquals('foo.bar.whiz.bang', Naming::createServiceName('CRM_Foo', 'Bar', 'Whiz', 'Bang'));
    $this->assertEquals('foo.bar.whiz.bang', Naming::createServiceName('CRM/Foo', 'Bar', 'Whiz', 'Bang'));
    $this->assertEquals('foo.bar.whiz.bang', Naming::createServiceName('Civi\\Foo', 'Bar', 'Whiz', 'Bang'));
    $this->assertEquals('foo.bar.whiz.bang', Naming::createServiceName('Civi/Foo', 'Bar', 'Whiz', 'Bang'));
  }

  public function testCoerceNamespace() {
    $this->assertEquals('CRM_Foobar', Naming::coerceNamespace('CRM_Foobar', 'auto'));
    $this->assertEquals('CRM_Foobar', Naming::coerceNamespace('CRM_Foobar', 'CRM'));
    $this->assertEquals('Civi\\Foobar', Naming::coerceNamespace('CRM_Foobar', 'Civi'));

    $this->assertEquals('Civi\\Foobar', Naming::coerceNamespace('Civi\\Foobar', 'auto'));
    $this->assertEquals('CRM_Foobar', Naming::coerceNamespace('Civi\\Foobar', 'CRM'));
    $this->assertEquals('Civi\\Foobar', Naming::coerceNamespace('Civi\\Foobar', 'Civi'));
  }

}
