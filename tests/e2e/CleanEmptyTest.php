<?php

namespace E2E;

class CleanEmptyTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civixcleanempty';

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
    $this->civixGenerateModule(static::getKey());
    chdir(static::getKey());

    $this->assertFileExists('info.xml');
  }

  public function testCleanup(): void {
    $mainPhp = static::getKey() . '.php';
    copy(__DIR__ . '/CleanEmptyTest.in.txt', $mainPhp);
    $this->assertEquals(0, $this->civix('upgrade')->execute([]));
    $this->assertFileEquals(__DIR__ . '/CleanEmptyTest.out.txt', $mainPhp);
  }

}
