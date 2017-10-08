<?php
echo "<?php\n";
?>

// Allow autoloading of PHPUnit helper classes in this extension.
$loader = new \Composer\Autoload\ClassLoader();
$loader->add('CRM_', __DIR__);
$loader->add('Civi\\', __DIR__);
$loader->register();

/**
* This is a generic test class for you extension, implementing PHPUnit test
*
*   TODO: Add more description/explanation in here
*/
class <?php echo $testClass ?> extends \PHPUnit_Framework_TestCase {

    /**
     * Setup Method is executed before the test is executed
     */
    public function setUp() {
        parent::setUp();
    }

    /**
     * Tear Down Method is executed after the test was executed
     * This should be used for cleanup
     */
    public function tearDown() {
        parent::tearDown();
    }

    /**
     * Example test case
     * TODO: Description
     */
    public function testExample() {
        $myBool = True;
        self::assertTrue($myBool, "The argument must be true to pass the test");
    }
}
