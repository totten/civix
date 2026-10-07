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
    $this->civixGenerateModule(static::getKey(), ['--compatibility' => '6.0']);
    chdir(static::getKey());
  }

  /**
   * Do the upgrade a full replay (`civix upgrade --start=0`).
   */
  public function testBasicUpgrade(): void {
    // Make an example
    $this->civixGeneratePage('MyPage', 'civicrm/thirty');
    $this->civixGenerateUpgrader(); /* TODO: Make this implicit with generate:entity */
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
    $this->civixGenerateUpgrader(); /* TODO: Make this implicit with generate:entity */
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
    $this->civixGenerateUpgrader(); /* TODO: Make this implicit with generate:entity */
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

  public function testUpgradeBumpCompatibilityYes(): void {
    $tester = static::civix('upgrade');
    $tester->setInputs(['yes']);
    $tester->execute([]);
    $result = $tester->getDisplay(TRUE);

    $this->assertStringContainsString('is EOL', $result);
    $this->assertStringContainsString('version is 6.4', $result);
    $this->assertStringContainsString('Set min compatibility to 6.4 in info.xml', $result);

    $xml = simplexml_load_file('info.xml');
    $vers = [];
    foreach ($xml->xpath('compatibility/ver') as $ver) {
      $vers[] = (string) $ver;
    }
    $this->assertContains('6.4', $vers);
    $this->assertNotContains('6.0', $vers);
  }

  public function testUpgradeBumpCompatibilityNo(): void {
    $tester = static::civix('upgrade');
    $tester->setInputs(['no']);
    $tester->execute([]);
    $result = $tester->getDisplay(TRUE);

    $this->assertStringContainsString('is EOL', $result);
    $this->assertStringContainsString('version is 6.4', $result);
    $this->assertStringNotContainsString('Set min compatibility to', $result);

    $xml = simplexml_load_file('info.xml');
    $vers = [];
    foreach ($xml->xpath('compatibility/ver') as $ver) {
      $vers[] = (string) $ver;
    }
    $this->assertContains('6.0', $vers);
    $this->assertNotContains('6.4', $vers);
  }

  public function testUpgradeCompatibilityAlreadyCurrent(): void {
    $this->civixInfoSet('compatibility/ver', '6.4');

    $tester = static::civix('upgrade');
    $tester->execute([]);
    $result = $tester->getDisplay(TRUE);

    $this->assertStringNotContainsString('is EOL', $result);
    $this->assertStringNotContainsString('Do you want to bump the minimum compatibility version', $result);
  }

  public function testUpgradeBumpCompatibilityCustomVersion(): void {
    $tester = static::civix('upgrade');
    $tester->setInputs(['6.10']);
    $tester->execute([]);
    $result = $tester->getDisplay(TRUE);

    $this->assertStringContainsString('is EOL', $result);
    $this->assertStringContainsString('Set min compatibility to 6.10 in info.xml', $result);

    $xml = simplexml_load_file('info.xml');
    $vers = [];
    foreach ($xml->xpath('compatibility/ver') as $ver) {
      $vers[] = (string) $ver;
    }
    $this->assertContains('6.10', $vers);
    $this->assertNotContains('6.0', $vers);
  }

  public function testUpgradeBumpCompatibilityShortY(): void {
    $tester = static::civix('upgrade');
    $tester->setInputs(['y']);
    $tester->execute([]);
    $result = $tester->getDisplay(TRUE);

    $this->assertStringContainsString('Set min compatibility to 6.4 in info.xml', $result);

    $xml = simplexml_load_file('info.xml');
    $vers = [];
    foreach ($xml->xpath('compatibility/ver') as $ver) {
      $vers[] = (string) $ver;
    }
    $this->assertContains('6.4', $vers);
  }

  public function testUpgradeBumpCompatibilityShortN(): void {
    $tester = static::civix('upgrade');
    $tester->setInputs(['n']);
    $tester->execute([]);
    $result = $tester->getDisplay(TRUE);

    $this->assertStringNotContainsString('Set min compatibility to', $result);

    $xml = simplexml_load_file('info.xml');
    $vers = [];
    foreach ($xml->xpath('compatibility/ver') as $ver) {
      $vers[] = (string) $ver;
    }
    $this->assertContains('6.0', $vers);
    $this->assertNotContains('6.4', $vers);
  }

  public function testUpgradeBumpCompatibilitySkippedForMajorVersionPlaceholder(): void {
    $this->civixInfoSet('compatibility/ver', '[civicrm.majorVersion]');

    $tester = static::civix('upgrade');
    $tester->execute([]);
    $result = $tester->getDisplay(TRUE);

    $this->assertStringNotContainsString('is EOL', $result);
    $this->assertStringNotContainsString('Do you want to bump the minimum compatibility version', $result);
    $this->assertStringNotContainsString('Set min compatibility to', $result);

    $xml = simplexml_load_file('info.xml');
    $vers = [];
    foreach ($xml->xpath('compatibility/ver') as $ver) {
      $vers[] = (string) $ver;
    }
    $this->assertContains('[civicrm.majorVersion]', $vers);
  }

  public function testUpgradeBumpCompatibilityUsesMockData(): void {
    $originalUrl = getenv('CIVIX_LATEST_STABLE_URL');
    $customMock = static::getWorkspacePath('custom-versions.json')->string();
    file_put_contents($customMock, json_encode([
      '6.6' => ['status' => 'stable'],
      '6.7' => ['status' => 'eol'],
    ]));
    putenv('CIVIX_LATEST_STABLE_URL=' . $customMock);

    try {
      $tester = static::civix('upgrade');
      $tester->setInputs(['yes']);
      $tester->execute([]);
      $result = $tester->getDisplay(TRUE);

      $this->assertStringContainsString('version is 6.6', $result);
      $this->assertStringContainsString('Set min compatibility to 6.6 in info.xml', $result);
    }
    finally {
      putenv('CIVIX_LATEST_STABLE_URL=' . $originalUrl);
      if (file_exists($customMock)) {
        unlink($customMock);
      }
    }
  }

}
