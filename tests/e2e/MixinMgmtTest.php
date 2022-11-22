<?php

namespace E2E;

class MixinMgmtTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_mixinrec';

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
    $this->civixGenerateModule(static::getKey(), ['--compatibility' => '5.0']);
    chdir(static::getKey());

    $this->assertFileExists('info.xml');
  }

  public function testDefaultMixins(): void {
    $this->assertFileGlobs([
      'mixin/polyfill.php' => 1,
      'mixin/setting-php@1.*.*.mixin.php' => 1,
      'mixin/case-xml@1.*.*.mixin.php' => 0,
      'mixin/menu-xml@1.*.*.mixin.php' => 0,
      'mixin/mgd-php@1.*.*.mixin.php' => 1,
    ]);
  }

  public function testChangeCompatibility(): void {
    $this->assertFileGlobs([
      'mixin/polyfill.php' => 1,
      'mixin/setting-php@1.*.*.mixin.php' => 1,
    ]);

    $this->civixInfoSet('compatibility/ver', '5.45');
    $upgrade = $this->civix('upgrade');
    $this->assertEquals(0, $upgrade->execute([]));

    $this->assertFileGlobs([
      'mixin/polyfill.php' => 0,
      'mixin/setting-php@1.*.*.mixin.php' => 0,
    ]);
  }

  public function testEnableAllDisableAll(): void {
    $this->assertFileGlobs([
      'mixin/polyfill.php' => 1,
      'mixin/setting-php@1.*.*.mixin.php' => 1,
    ]);

    $enableAll = $this->civix('mixin');
    $this->assertEquals(0, $enableAll->execute(['--enable-all' => TRUE]));

    $this->assertFileGlobs([
      'mixin/polyfill.php' => 1,
      'mixin/setting-php@1.*.*.mixin.php' => 1,
      'mixin/case-xml@1.*.*.mixin.php' => 1,
      'mixin/menu-xml@1.*.*.mixin.php' => 1,
      'mixin/mgd-php@1.*.*.mixin.php' => 1,
    ]);

    $disableAll = $this->civix('mixin');
    $this->assertEquals(0, $disableAll->execute(['--disable-all' => TRUE]));

    $this->assertFileGlobs([
      'mixin/polyfill.php' => 1,
      'mixin/setting-php@1.*.*.mixin.php' => 0,
      'mixin/case-xml@1.*.*.mixin.php' => 0,
      'mixin/menu-xml@1.*.*.mixin.php' => 0,
      'mixin/mgd-php@1.*.*.mixin.php' => 0,
    ]);
  }

  public function testAddPageFor530(): void {
    $this->civixInfoSet('compatibility/ver', '5.30');

    $this->assertFileGlobs([
      'mixin/polyfill.php' => 1,
      'mixin/setting-php@1.*.*.mixin.php' => 1,
      'mixin/menu-xml@1.*.*.mixin.php' => 0,
    ]);

    $this->civixGeneratePage('Thirty', 'civicrm/thirty');

    $this->assertFileGlobs([
      'mixin/polyfill.php' => 1,
      'mixin/menu-xml@1.*.*.mixin.php' => 1,
    ]);
    $content = file_get_contents('civix_mixinrec.civix.php');
    $this->assertRegExp('|function _civix_mixinrec_civix_mixin_polyfill\(\) \{|', $content);
    $this->assertRegExp('|_civix_mixinrec_civix_mixin_polyfill\(\);|', $content);
  }

  public function testAddPageFor545(): void {
    $this->civixInfoSet('compatibility/ver', '5.45');

    $this->assertFileGlobs([
      'mixin/polyfill.php' => 1,
      'mixin/setting-php@1.*.*.mixin.php' => 1,
      'mixin/menu-xml@1.*.*.mixin.php' => 0,
    ]);

    $this->civixGeneratePage('FortyFive', 'civicrm/forty-five');

    // Not only do we omit 'menu-xml' backport... but we also clean-up the extras...
    $this->assertFileGlobs([
      'mixin/polyfill.php' => 0,
      'mixin/setting-php@1.*.*.mixin.php' => 0,
      'mixin/menu-xml@1.*.*.mixin.php' => 0,
    ]);

    $content = file_get_contents('civix_mixinrec.civix.php');
    $this->assertNotRegExp('|function _civix_mixinrec_civix_mixin_polyfill\(\) \{|', $content);
    $this->assertNotRegExp('|_civix_mixinrec_civix_mixin_polyfill\(\);|', $content);
  }

}
