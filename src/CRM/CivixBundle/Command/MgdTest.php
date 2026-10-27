<?php

namespace CRM\CivixBundle\Command;

use PHPUnit\Framework\TestCase;

class MgdTest extends TestCase {

  public function testNameContainsShortName(): void {
    // Matching with exact underscores
    $this->assertTrue(Mgd::nameContainsShortName('my_ext_foo', 'my_ext'));
    // Matching without underscores in name (e.g. CamelCase or collapsed)
    $this->assertTrue(Mgd::nameContainsShortName('MyExtFoo', 'my_ext'));
    $this->assertTrue(Mgd::nameContainsShortName('myextfoo', 'my_ext'));
    // Matching with hyphens in short name
    $this->assertTrue(Mgd::nameContainsShortName('my_ext_foo', 'my-ext'));
    $this->assertTrue(Mgd::nameContainsShortName('myextfoo', 'my-ext'));
    // Matching with plain short name
    $this->assertTrue(Mgd::nameContainsShortName('my_ext_foo', 'myext'));
    // Matching in middle/suffix of record name
    $this->assertTrue(Mgd::nameContainsShortName('custom_myext_display', 'my_ext'));
    $this->assertTrue(Mgd::nameContainsShortName('custom_my_ext_display', 'myext'));

    // Non-matching cases
    $this->assertFalse(Mgd::nameContainsShortName('foo_bar', 'my_ext'));
    $this->assertFalse(Mgd::nameContainsShortName('other_ext_foo', 'my_ext'));
    $this->assertFalse(Mgd::nameContainsShortName('', 'my_ext'));
    $this->assertFalse(Mgd::nameContainsShortName('foo', ''));
  }

  public function testFormatUniqueName(): void {
    $this->assertEquals('my_ext_my_search', Mgd::formatUniqueName('my_search', 'my_ext', 'SavedSearch', 255));
    $this->assertEquals('my_ext_my_group', Mgd::formatUniqueName('my_group', 'my_ext', 'OptionGroup', 64));
    // Afform with afform prefix
    $this->assertEquals('afform_my_ext_MyForm', Mgd::formatUniqueName('afformMyForm', 'my_ext', 'Afform', 255));
    $this->assertEquals('afform_my_ext_my_form', Mgd::formatUniqueName('afform_my_form', 'my_ext', 'Afform', 255));
    $this->assertEquals('afform_my_ext', Mgd::formatUniqueName('afform', 'my_ext', 'Afform', 255));
    // Afform with afsearch prefix
    $this->assertEquals('afsearch_my_ext_AdminCustomFields', Mgd::formatUniqueName('afsearchAdminCustomFields', 'my_ext', 'Afform', 255));
    $this->assertEquals('afsearch_my_ext_admin_custom_fields', Mgd::formatUniqueName('afsearch_admin_custom_fields', 'my_ext', 'Afform', 255));
    $this->assertEquals('afsearch_my_ext', Mgd::formatUniqueName('afsearch', 'my_ext', 'Afform', 255));
    // Afform without afform/afsearch prefix
    $this->assertEquals('my_ext_custom_form', Mgd::formatUniqueName('custom_form', 'my_ext', 'Afform', 255));
  }

}
