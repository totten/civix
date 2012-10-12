<?php
echo "<?php\n";
?>

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * FIXME
 */
class <?php echo $testClass ?> extends CiviUnitTestCase {
  function setUp() {
    // If your test manipulates any SQL tables, then you should truncate
    // them to ensure a consisting starting point for all tests
    // $this->quickCleanup(array('example_table_name'));
    parent::setUp();
  }

  function tearDown() {
    parent::tearDown();
  }

  /**
   * Test that 1^2 == 1
   */
  function testSquareOfOne() {
    $this->assertEquals(1, 1*1);
  }

  /**
   * Test that 8^2 == 64
   */
  function testSquareOfEight() {
    $this->assertEquals(64, 8*8);
  }
}