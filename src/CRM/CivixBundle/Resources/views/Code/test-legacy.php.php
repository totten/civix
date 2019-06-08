<?php
echo "<?php\n";

if ($testNamespace) {
  echo "namespace $testNamespace;\n";
}
$_namespace = preg_replace(':/:', '_', $namespace);
?>

use <?php echo $_namespace ?>_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;

/**
 * FIXME - Add test description.
 *
 * Note: Legacy test cases are based on CiviUnitTestCase. These should work about
 * as well (or as poorly) as before. This generator is provided primarily as a way
 * test backward compatibility.
 *
 * @group headless
 * @group legacy
 */
class <?php echo $testClass ?> extends \CiviUnitTestCase implements HeadlessInterface {

  public function setUpHeadless() {
    // `CiviTestListener`+`HeadlessInterface` has some clever tricks for
    // bootstrapping which make it easier to use phpunit CLI.
    // However, there's not much point in using setupHeadless() with CiviUnitTestCase
    // because CiviUnitTestCase performs its own special setup/teardown logic.
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Example: Test that a version is returned.
   */
  public function testWellFormedVersion() {
    $this->assertNotEmpty(E::SHORT_NAME);
    $this->assertRegExp('/^([0-9\.]|alpha|beta)*$/', \CRM_Utils_System::version());
  }

  /**
   * Example: Test that we're using a fake CMS.
   */
  public function testWellFormedUF() {
    $this->assertEquals('UnitTests', CIVICRM_UF);
  }

}
