<?php

namespace CRM\CivixBundle\Utils;

class NamingTest extends \PHPUnit_Framework_TestCase {

  public function getFullNameExamples() {
    return [
      // [$name, $expectValid, $expectShort, $expectCamel]
      ['org.example.foo', TRUE, 'foo', 'Foo'],
      ['org.example.foo-bar-whiz', TRUE, 'foo_bar_whiz', 'FooBarWhiz'],
      ['.org.example.foo', FALSE, NULL, NULL],
      ['civicrm-foo-bar', TRUE, 'foo_bar', 'FooBar'],
      ['foo-bar', TRUE, 'foo_bar', 'FooBar'],
      ['foo--bar', FALSE, NULL, NULL],
      ['foo-2-foo', TRUE, 'foo_2_foo', 'Foo2Foo'],
      ['-foo', FALSE, NULL, NULL],
      ['foo-', FALSE, NULL, NULL],
      ['civicrm-foo-bar.', FALSE, NULL, NULL],
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
      return;
    }
    $this->assertEquals($expectShort, Naming::createShortName($name));
  }

  /**
   * @dataProvider getFullNameExamples
   */
  public function testCreateCamelName($name, $expectValid, $expectShort, $expectCamel) {
    if (!$expectValid) {
      return;
    }
    $this->assertEquals($expectCamel, Naming::createCamelName($name));
  }

}
