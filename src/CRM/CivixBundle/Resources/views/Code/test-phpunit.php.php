<?php
echo "<?php\n";
?>

/**
 * This is a generic test class for the extension (implemented with PHPUnit).
 */
class <?php echo $testClass ?> extends \PHPUnit\Framework\TestCase {

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Simple example test case.
   *
   * Note how the function name begins with the word "test".
   */
  public function testExample() {
    self::assertTrue(TRUE, "The argument must be true to pass the test");
  }

}
