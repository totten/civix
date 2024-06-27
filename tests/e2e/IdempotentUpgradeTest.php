<?php

namespace E2E;

/**
 * What happens if you take a new extension and run an upgrade on it?
 * The result should match.
 */
class IdempotentUpgradeTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_upgradereset';

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
    $this->civixGenerateModule(static::getKey(), ['--compatibility' => '5.0']);
    chdir(static::getKey());
  }

  /**
   * Do the upgrade a full replay (`civix upgrade --start=0`).
   */
  public function testBasicUpgrade(): void {
    // Make an example
    $this->civixGeneratePage('MyPage', 'civicrm/thirty');
    $this->civixGenerateEntity('MyEntity');
    $start = $this->getExtSnapshot();

    // Do the upgrade
    $result = $this->civixUpgrade()->getDisplay(TRUE);
    $expectLines = [
      'Incremental upgrades',
      'General upgrade',
    ];
    $this->assertStringSequence($expectLines, $result);
    $this->assertDoesNotMatchRegularExpression(';Upgrade v([\d\.]+) => v([\d\.]+);', $result);

    // Compare before+after
    $end = $this->getExtSnapshot();
    $this->assertEquals($start, $end);
  }

  /**
   * Do the upgrade a full replay (`civix upgrade --start=0`).
   */
  public function testResetVersion0(): void {
    // Make an example
    $this->civixGeneratePage('MyPage', 'civicrm/thirty');
    $this->civixGenerateEntity('MyEntity');
    $start = $this->getExtSnapshot();

    // Do the upgrade
    $result = $this->civixUpgrade(['--start' => '0'])->getDisplay(TRUE);
    $expectLines = [
      'Incremental upgrades',
      'Upgrade v13.10.0 => v16.10.0',
      'Upgrade v22.05.0 => v22.05.2',
      'General upgrade',
    ];
    $this->assertStringSequence($expectLines, $result);

    // Compare before+after
    $end = $this->getExtSnapshot();
    $this->assertEquals($start, $end);
  }

  /**
   * Do the upgrade a full replay (`civix upgrade --start=22.01.0`).
   */
  public function testResetVersion2201(): void {
    // Make an example
    $this->civixGeneratePage('MyPage', 'civicrm/thirty');
    $this->civixGenerateEntity('MyEntity');
    $start = $this->getExtSnapshot();

    // Do the upgrade
    $result = $this->civixUpgrade(['--start' => '22.01.0'])->getDisplay(TRUE);
    $expectLines = [
      'Incremental upgrades',
      'Upgrade v22.05.0 => v22.05.2',
      'General upgrade',
    ];
    $this->assertStringSequence($expectLines, $result);
    $this->assertStringNotContainsString('Upgrade v13.10.0 => v16.10.0', $result);

    // Compare before+after
    $end = $this->getExtSnapshot();
    $this->assertEquals($start, $end);
  }

}
