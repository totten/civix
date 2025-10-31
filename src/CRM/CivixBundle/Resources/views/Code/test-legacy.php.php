<?php
echo "<?php\n";
echo "declare(strict_types = 1);\n";
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

  /**
   * Setup for when Headless interface is implemented.
   *
   * `CiviTestListener`+`HeadlessInterface` has some clever tricks for
   * bootstrapping which make it easier to use phpunit CLI.
   * However, there's not much point in using setupHeadless() with CiviUnitTestCase
   * because CiviUnitTestCase performs its own special setup/teardown logic.
   */
  public function setUpHeadless(): void {}

  /**
   * Setup any fixtures required for the tests in this class.
   */
  public function setUp(): void {
    parent::setUp();
  }

  /**
   * Return the database to the original state..
   */
  public function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Example: Test that a version is returned.
   */
  public function testWellFormedVersion(): void {
    $this->assertNotEmpty(E::SHORT_NAME);
    $this->assertMatchesRegularExpression('/^([0-9\.]|alpha|beta)*$/', \CRM_Utils_System::version());
  }

  /**
   * Example: Test that we're using a fake CMS.
   */
  public function testWellFormedUF(): void {
    $this->assertEquals('UnitTests', CIVICRM_UF);
  }

}
