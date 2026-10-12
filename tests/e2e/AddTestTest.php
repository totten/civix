<?php

namespace E2E;

use ProcessHelper\ProcessHelper;

/**
 * @group e2e
 */
class AddTestTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_addtest';

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
    $this->civixGenerateModule(static::getKey());
    chdir(static::getKey());

    $this->assertFileGlobs([
      'info.xml' => 1,
      'civix_addtest.php' => 1,
      'civix_addtest.civix.php' => 1,
    ]);
  }

  public function testAddTestWithLineBreak(): void {
    $this->assertFileGlobs([
      'tests/phpunit/Civi/CivixAddtest/SampleTest.php' => 0,
    ]);

    $this->civixGenerateTest("Civi\\CivixAddtest\\SampleTest\n");

    $this->assertFileGlobs([
      'tests/phpunit/Civi/CivixAddtest/SampleTest.php' => 1,
    ]);

    $code = file_get_contents('tests/phpunit/Civi/CivixAddtest/SampleTest.php');
    $this->assertStringContainsString('class SampleTest extends', $code);

    ProcessHelper::runOk('php -l tests/phpunit/Civi/CivixAddtest/SampleTest.php');
  }

}
