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

    $this->assertFileExists('info.xml');
    $this->assertFileExists('civix_addpage.php');
    $this->assertFileExists('civix_addpage.civix.php');
  }

  public function testAddPage() {
    $this->assertFileNotExists('CRM/CivixAddpage/Page/MyPage.php');
    $this->assertFileNotExists('templates/CRM/CivixAddpage/Page/MyPage.tpl');
    $this->assertFileNotExists('xml/Menu/civix_addpage.xml');

    $this->civixGeneratePage('MyPage', 'civicrm/thirty');

    $this->assertFileExists('CRM/CivixAddpage/Page/MyPage.php');
    $this->assertFileExists('templates/CRM/CivixAddpage/Page/MyPage.tpl');
    $this->assertFileExists('xml/Menu/civix_addpage.xml');
  }

}
