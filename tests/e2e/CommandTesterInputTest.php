<?php

namespace E2E;

use CRM\CivixBundle\Test\SubProcessCommandTester;

class CommandTesterInputTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_testerinput';

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
    $this->civixGenerateModule(static::getKey());
    chdir(static::getKey());
  }

  public function testInputWithIsolationOn(): void {
    putenv('CIVIX_TEST_ISOLATION=on');
    $tester = static::civix('generate:service');
    $this->assertInstanceOf(SubProcessCommandTester::class, $tester);
    $tester->setInputs(['civix_testerinput.on.svc']);
    $tester->execute([]);
    $this->assertTesterOk($tester);

    $this->assertFileGlobs([
      'CRM/CivixTesterinput/On/Svc.php' => 1,
    ]);
  }

  public function testInputWithIsolationOff(): void {
    putenv('CIVIX_TEST_ISOLATION=off');
    $tester = static::civix('generate:service');
    $this->assertInstanceOf(\Symfony\Component\Console\Tester\CommandTester::class, $tester);
    $tester->setInputs(['civix_testerinput.off.svc']);
    $tester->execute([]);
    $this->assertTesterOk($tester);

    $this->assertFileGlobs([
      'CRM/CivixTesterinput/Off/Svc.php' => 1,
    ]);
  }

}
