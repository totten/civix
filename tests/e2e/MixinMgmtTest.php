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
    $this->assertFileGlobs(['mixin/polyfill.php' => 1]);
    $this->assertMixinStatuses([
      'setting-php@1' => 'on+backport',
    ]);

    $this->civixInfoSet('compatibility/ver', '5.45');
    $upgrade = $this->civix('upgrade');
    $this->assertEquals(0, $upgrade->execute([]));

    $this->assertFileGlobs(['mixin/polyfill.php' => 0]);
    $this->assertMixinStatuses([
      'setting-php@1' => 'on',
    ]);
  }

  /**
   * In this example, we enable some of the original mixins - but omit some of the newer ones.
   * We go back-and-forth, with enabling and disabling. At each step, we observe the status
   * of the polyfill and the mixins.
   */
  public function testEnableSomeDisableAll(): void {
    $this->assertEquals('5.27', trim($this->civixInfoGet('compatibility/ver')->getDisplay()));
    $this->assertFileGlobs(['mixin/polyfill.php' => 1]);
    $this->assertMixinStatuses([
      'setting-php@1' => 'on+backport',
      'menu-xml@1' => 'off',
      'scan-classes@1' => 'off',
    ]);

    $enableAll = $this->civix('mixin');
    $this->assertEquals(0, $enableAll->execute(['--enable' => 'menu-xml@1.0.0,mgd-php@1.0.0']));

    $this->assertEquals('5.27', trim($this->civixInfoGet('compatibility/ver')->getDisplay()));
    $this->assertFileGlobs(['mixin/polyfill.php' => 1]);
    $this->assertMixinStatuses([
      'setting-php@1' => 'on+backport',
      'menu-xml@1' => 'on+backport',
      'mgd-php@1' => 'on+backport',
      'scan-classes@1' => 'off',
    ]);

    $disableAll = $this->civix('mixin');
    $this->assertEquals(0, $disableAll->execute(['--disable-all' => TRUE]));

    $this->assertEquals('5.27', trim($this->civixInfoGet('compatibility/ver')->getDisplay()));
    $this->assertFileGlobs(['mixin/polyfill.php' => 1]);
    $this->assertMixinStatuses([
      'setting-php@1' => 'off',
      'menu-xml@1' => 'off',
      'mgd-php@1' => 'off',
      'scan-classes@1' => 'off',
    ]);
  }

  /**
   * In this example, we enable all currently known mixins. This may incidentally the `<compatibility>`
   * and the set of backports.
   */
  public function testEnableAllDisableAll(): void {
    $this->assertEquals('5.27', trim($this->civixInfoGet('compatibility/ver')->getDisplay()));
    $this->assertFileGlobs(['mixin/polyfill.php' => 1]);
    $this->assertMixinStatuses([
      'setting-php@1' => 'on+backport',
      'menu-xml@1' => 'off',
      'scan-classes@1' => 'off',
    ]);

    $enableAll = $this->civix('mixin');
    $this->assertEquals(0, $enableAll->execute(['--enable-all' => TRUE]));
    // Enabling _all_ mixins brings in 'scan-classes@1', which causes the <ver> to go higher (5.51).

    $this->assertEquals('5.51', trim($this->civixInfoGet('compatibility/ver')->getDisplay()));
    $this->assertFileGlobs(['mixin/polyfill.php' => 0]);
    $this->assertMixinStatuses([
      // These are active but don't need backports (for 5.51).
      'setting-php@1' => 'on',
      'menu-xml@1' => 'on',
      'scan-classes@1' => 'on',
      'mgd-php@1' => 'on',
    ]);

    $disableAll = $this->civix('mixin');
    $this->assertEquals(0, $disableAll->execute(['--disable-all' => TRUE]));

    $this->assertEquals('5.51', trim($this->civixInfoGet('compatibility/ver')->getDisplay()));
    $this->assertFileGlobs(['mixin/polyfill.php' => 0]);
    $this->assertMixinStatuses([
      'setting-php@1' => 'off',
      'menu-xml@1' => 'off',
      'scan-classes@1' => 'off',
      'mgd-php@1' => 'off',
    ]);
  }

  public function testAddPageFor530(): void {
    $this->civixInfoSet('compatibility/ver', '5.30');

    $this->assertFileGlobs(['mixin/polyfill.php' => 1]);
    $this->assertMixinStatuses([
      'setting-php@1' => 'on+backport',
      'menu-xml@1' => 'off',
    ]);

    $this->civixGeneratePage('Thirty', 'civicrm/thirty');

    $this->assertFileGlobs(['mixin/polyfill.php' => 1]);
    $this->assertMixinStatuses([
      'setting-php@1' => 'on+backport',
      'menu-xml@1' => 'on+backport',
    ]);

    $content = file_get_contents('civix_mixinrec.civix.php');
    $this->assertMatchesRegularExpression('|function _civix_mixinrec_civix_mixin_polyfill\(\) \{|', $content);
    $this->assertMatchesRegularExpression('|_civix_mixinrec_civix_mixin_polyfill\(\);|', $content);
  }

  public function testAddPageFor545(): void {
    $this->civixInfoSet('compatibility/ver', '5.45');

    $this->assertFileGlobs(['mixin/polyfill.php' => 1]);
    $this->assertMixinStatuses([
      'setting-php@1' => 'on+backport',
      'menu-xml@1' => 'off',
    ]);

    $this->civixGeneratePage('FortyFive', 'civicrm/forty-five');

    // Not only do we omit 'menu-xml' backport... but we also clean-up the extras...
    $this->assertFileGlobs(['mixin/polyfill.php' => 0]);
    $this->assertMixinStatuses([
      'setting-php@1' => 'on',
      'menu-xml@1' => 'on',
    ]);

    $content = file_get_contents('civix_mixinrec.civix.php');
    $this->assertDoesNotMatchRegularExpression('|function _civix_mixinrec_civix_mixin_polyfill\(\) \{|', $content);
    $this->assertDoesNotMatchRegularExpression('|_civix_mixinrec_civix_mixin_polyfill\(\);|', $content);
  }

}
