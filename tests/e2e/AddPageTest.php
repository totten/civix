<?php

namespace E2E;

class AddPageTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_addpage';

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
    $this->civixGenerateModule(static::getKey());
    chdir(static::getKey());

    $this->assertFileGlobs([
      'info.xml' => 1,
      'civix_addpage.php' => 1,
      'civix_addpage.civix.php' => 1,
    ]);
  }

  public function testAddPage(): void {
    $this->assertFileGlobs([
      'CRM/CivixAddpage/Page/MyPage.php' => 0,
      'templates/CRM/CivixAddpage/Page/MyPage.tpl' => 0,
      'xml/Menu/civix_addpage.xml' => 0,
    ]);

    $this->civixGeneratePage('MyPage', 'civicrm/thirty');

    $this->assertFileGlobs([
      'CRM/CivixAddpage/Page/MyPage.php' => 1,
      'templates/CRM/CivixAddpage/Page/MyPage.tpl' => 1,
      'xml/Menu/civix_addpage.xml' => 1,
    ]);
  }

}
