<?php
echo "<?php\n";

if ($testNamespace) {
  echo "namespace $testNamespace;\n";
}
?>

use Civi\Test\EndToEndInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - The global variable $_CV has some properties which may be useful, such as:
 *    ADMIN_USER, ADMIN_PASS, ADMIN_EMAIL, DEMO_USER, DEMO_PASS, DEMO_EMAIL.
 *  - To spawn a separate thread and execute some Civi logic, use the cv() helper.
 *
 * @group e2e
 * @see cv
 */
class <?php echo $testClass ?> extends \PHPUnit_Framework_TestCase implements EndToEndInterface {

  public static function setUpBeforeClass() {
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md

    // Example: Install this extension. Don't care about anything else.
    \Civi\Test::e2e()->installMe(__DIR__)->apply();

    // Example: Uninstall all extensions except this one.
    // \Civi\Test::e2e()->uninstall('*')->installMe(__DIR__)->apply();

    // Example: Install only core civicrm extensions.
    // \Civi\Test::e2e()->uninstall('*')->install('org.civicrm.*')->apply();
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
    $this->assertRegExp('/^([0-9\.]|alpha|beta)*$/', \CRM_Utils_System::version());
  }

  /**
   * Example: Test that we're using a real CMS (Drupal, WordPress, etc).
   */
  public function testWellFormedUF() {
    $this->assertRegExp('/^(Drupal|Backdrop|WordPress|Joomla)/', CIVICRM_UF);
  }

}
