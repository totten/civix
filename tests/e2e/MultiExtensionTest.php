<?php

namespace E2E;

use CRM\CivixBundle\Test\TestHelper as TH;

/**
 * For this test, we create and enable two extensions.
 * They should coexist.
 */
class MultiExtensionTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $keys = ['apple', 'banana'];

  public static $key = '**INVALID**';

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    TH::runOk('civibuild restore');

    foreach (static::$keys as $key) {
      static::cleanDir($key);
      $this->civixGenerateModule($key);
    }

    foreach ($this->visitExts() as $name) {
      $this->assertFileGlobs([
        'info.xml' => 1,
        "$name.php" => 1,
        "$name.civix.php" => 1,
      ]);
    }
  }

  protected function tearDown(): void {
    chdir(static::getWorkspacePath());
    TH::runOk('civibuild restore');
    \Civix::ioStack()->reset();
  }

  public function testAddPage(): void {
    foreach ($this->visitExts() as $name) {
      $camel = ucfirst($name);
      $this->assertFileGlobs(["CRM/$camel/Page/My$camel.php" => 0]);
      $this->civixGeneratePage("My$camel", "civicrm/example/$name");
      $this->assertFileGlobs(["CRM/$camel/Page/My$camel.php" => 1]);

      TH::runOK('cv en ' . escapeshellarg($name));
    }

    foreach ($this->visitExts() as $name) {
      $camel = ucfirst($name);
      $getPage = TH::runOK("cv api4 Route.get +w path=civicrm/example/$name +s page_callback");
      $this->assertTrue((bool) preg_match("/CRM_{$camel}_Page_My{$camel}/", $getPage->getOutput()), "Route 'civicrm/example/$name' should be registered");
    }
  }

  protected function visitExts(): \Generator {
    foreach (static::$keys as $key) {
      static::$key = $key;
      chdir(static::getWorkspacePath());
      chdir($key);
      yield $key;
    }
    chdir(static::getWorkspacePath());
  }

}
