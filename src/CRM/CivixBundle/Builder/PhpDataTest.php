<?php

namespace CRM\CivixBundle\Builder;

/**
 * @group unit
 */
class PhpDataTest extends \PHPUnit\Framework\TestCase {

  private $tempDir;

  public function setUp(): void {
    parent::setUp();
    $this->tempDir = sys_get_temp_dir() . '/civix_test_' . uniqid();
    mkdir($this->tempDir);
  }

  public function tearDown(): void {
    parent::tearDown();
    if (is_dir($this->tempDir)) {
      array_map('unlink', glob("$this->tempDir/*"));
      rmdir($this->tempDir);
    }
  }

  public function testExcludeTsWithTopLevelKey() {
    $testFile = $this->tempDir . '/test_exclude.php';
    $ctx = [];

    $builder = new PhpData($testFile);
    $builder->useExtensionUtil('TestExt_ExtensionUtil');
    $builder->useTs(['title', 'label', 'description']);
    $builder->excludeTs(['metadata']);

    $builder->set([
      'title' => 'Main Title',
      'label' => 'Main Label',
      'metadata' => [
        'title' => 'Metadata Title',
        'label' => 'Metadata Label',
        'description' => 'Metadata Description',
      ],
    ]);

    $output = new \Symfony\Component\Console\Output\BufferedOutput();
    $builder->save($ctx, $output);

    $content = file_get_contents($testFile);

    // Main title and label should use E::ts
    $this->assertStringContainsString("E::ts('Main Title')", $content);
    $this->assertStringContainsString("E::ts('Main Label')", $content);

    // Metadata fields should NOT use E::ts
    $this->assertStringNotContainsString("E::ts('Metadata Title')", $content);
    $this->assertStringNotContainsString("E::ts('Metadata Label')", $content);
    $this->assertStringNotContainsString("E::ts('Metadata Description')", $content);

    // Metadata fields should be plain strings
    $this->assertStringContainsString("'Metadata Title'", $content);
    $this->assertStringContainsString("'Metadata Label'", $content);
    $this->assertStringContainsString("'Metadata Description'", $content);
  }

  public function testExcludeTsWithNestedStructure() {
    $testFile = $this->tempDir . '/test_nested.php';
    $ctx = [];

    $builder = new PhpData($testFile);
    $builder->useExtensionUtil('TestExt_ExtensionUtil');
    $builder->useTs(['title', 'label']);
    $builder->excludeTs(['config']);

    $builder->set([
      'title' => 'Root Title',
      'config' => [
        'title' => 'Config Title',
        'nested' => [
          'title' => 'Deep Config Title',
          'label' => 'Deep Config Label',
        ],
      ],
    ]);

    $output = new \Symfony\Component\Console\Output\BufferedOutput();
    $builder->save($ctx, $output);

    $content = file_get_contents($testFile);

    // Root title should use E::ts
    $this->assertStringContainsString("E::ts('Root Title')", $content);

    // All config fields (even nested) should NOT use E::ts
    $this->assertStringNotContainsString("E::ts('Config Title')", $content);
    $this->assertStringNotContainsString("E::ts('Deep Config Title')", $content);
    $this->assertStringNotContainsString("E::ts('Deep Config Label')", $content);
  }

  public function testExcludeTsWithMultipleExcludedKeys() {
    $testFile = $this->tempDir . '/test_multiple.php';
    $ctx = [];

    $builder = new PhpData($testFile);
    $builder->useExtensionUtil('TestExt_ExtensionUtil');
    $builder->useTs(['title', 'description']);
    $builder->excludeTs(['metadata', 'raw_data']);

    $builder->set([
      'title' => 'Main Title',
      'metadata' => [
        'title' => 'Meta Title',
      ],
      'raw_data' => [
        'description' => 'Raw Description',
      ],
      'description' => 'Main Description',
    ]);

    $output = new \Symfony\Component\Console\Output\BufferedOutput();
    $builder->save($ctx, $output);

    $content = file_get_contents($testFile);

    // Main fields should use E::ts
    $this->assertStringContainsString("E::ts('Main Title')", $content);
    $this->assertStringContainsString("E::ts('Main Description')", $content);

    // Excluded sections should NOT use E::ts
    $this->assertStringNotContainsString("E::ts('Meta Title')", $content);
    $this->assertStringNotContainsString("E::ts('Raw Description')", $content);
  }

}