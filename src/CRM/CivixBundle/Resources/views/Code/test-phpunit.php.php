<?php
echo "<?php\n";
?>

// Allow autoloading of PHPUnit helper classes in this extension.
$loader = new \Composer\Autoload\ClassLoader();
$loader->add('CRM_', __DIR__);
$loader->add('Civi\\', __DIR__);
$loader->register();

/**
 * This is a generic test class for the extension, implementing PHPUnit tests.
 *
 * Testing functions must begin 'test' to be included in the PhpUnit tests.
 */
class <?php echo $testClass ?> extends \PHPUnit_Framework_TestCase {

    /**
     * Setup Method is executed before the test is executed (optional)
     */
    public function setUp() {
        parent::setUp();
    }

    /**
     * Tear Down Method is executed after the test was executed (optional)
     * This can be used for cleanup
     */
    public function tearDown() {
        parent::tearDown();
    }

    /**
     * Example test case
     *  Simple test if a boolean expression is true. This is implemented to show
     *  an example for a test function
     */
    public function testExample() {
        $myBool = True;
        self::assertTrue($myBool, "The argument must be true to pass the test");
    }
}
