<?php

namespace E2E;

class IncludePathUpgradeTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_incpath';

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
    $this->civixGenerateModule(static::getKey(), [
      '--compatibility' => '6.4',
    ]);
    chdir(static::getKey());

    $this->assertFileExists('info.xml');
  }

  public function testNewModuleExcludesIncludePath(): void {
    $content = file_get_contents(static::getKey() . '.civix.php');
    $this->assertStringContainsString('function _' . static::getKey() . '_civix_civicrm_config($config = NULL)', $content);
    $this->assertStringNotContainsString('set_include_path', $content);
    $this->assertStringNotContainsString('get_include_path', $content);
  }

  public function testUpgradeCleanExtensionNonInteractive(): void {
    $upgrade = $this->civixUpgrade(['--start' => '26.10.1']);
    $display = $upgrade->getDisplay();

    $this->assertStringContainsString('PHP Include Path Optimization', $display);
    $this->assertStringContainsString('Civix has scanned this extension and found no code', $display);
    $this->assertStringContainsString('Skipped adding the "include-path@1" mixin', $display);
    $this->assertMixinStatuses([
      'include-path@1' => 'off',
    ]);
  }

  public function testUpgradeCleanExtensionUserAccepts(): void {
    $tester = static::civix('upgrade');
    $tester->setInputs(['yes']);
    $tester->execute(['--start' => '26.10.1']);
    $this->assertTesterOk($tester);

    $display = $tester->getDisplay();
    $this->assertStringContainsString('PHP Include Path Optimization', $display);
    $this->assertStringContainsString('Added "include-path@1" mixin', $display);
    $this->assertMixinStatuses([
      'include-path@1' => 'on+backport',
    ]);
  }

  public function testUpgradeCleanExtensionUserDeclines(): void {
    $tester = static::civix('upgrade');
    $tester->setInputs(['no']);
    $tester->execute(['--start' => '26.10.1']);
    $this->assertTesterOk($tester);

    $display = $tester->getDisplay();
    $this->assertStringContainsString('PHP Include Path Optimization', $display);
    $this->assertStringContainsString('Skipped adding the "include-path@1" mixin', $display);
    $this->assertMixinStatuses([
      'include-path@1' => 'off',
    ]);
  }

  public function testUpgradeWithReasonsUserDeclines(): void {
    mkdir('api/v3', 0777, TRUE);
    file_put_contents('api/v3/SomeEntity.php', "<?php\n");

    $tester = static::civix('upgrade');
    $tester->setInputs(['no']);
    $tester->execute(['--start' => '26.10.1']);
    $this->assertTesterOk($tester);

    $display = $tester->getDisplay();
    $this->assertStringContainsString('PHP Include Path Mixin', $display);
    $this->assertStringContainsString('Extension defines CiviCRM API v3 files (api/v3/)', $display);
    $this->assertStringContainsString('Skipped adding the "include-path@1" mixin', $display);
    $this->assertMixinStatuses([
      'include-path@1' => 'off',
    ]);
  }

  public function testUpgradeSkippedWhenMixinAlreadyPresent(): void {
    $this->civixMixin(['--enable' => 'include-path@1.0.0']);
    $this->assertMixinStatuses([
      'include-path@1' => 'on+backport',
    ]);

    mkdir('api/v3', 0777, TRUE);
    file_put_contents('api/v3/SomeEntity.php', "<?php\n");

    $upgrade = $this->civixUpgrade(['--start' => '26.10.1']);
    $this->assertStringNotContainsString('PHP Include Path', $upgrade->getDisplay());
    $this->assertMixinStatuses([
      'include-path@1' => 'on+backport',
    ]);
  }

  /**
   * @dataProvider getReasonScenarios
   */
  public function testUpgradeDetectsReasons(callable $setup, string $expectedReason): void {
    $setup();

    $upgrade = $this->civixUpgrade(['--start' => '26.10.1']);
    $display = $upgrade->getDisplay();

    $this->assertStringContainsString('PHP Include Path Mixin', $display);
    $this->assertStringContainsString($expectedReason, $display);
    $this->assertStringContainsString('Added "include-path@1" mixin', $display);
    $this->assertMixinStatuses([
      'include-path@1' => 'on+backport',
    ]);
  }

  public function getReasonScenarios(): array {
    return [
      'api3' => [
        function (): void {
          mkdir('api/v3', 0777, TRUE);
          file_put_contents('api/v3/SomeEntity.php', "<?php\n");
        },
        'Extension defines CiviCRM API v3 files (api/v3/)',
      ],
      'classOverride' => [
        function (): void {
          mkdir('CRM/Contact/BAO', 0777, TRUE);
          file_put_contents('CRM/Contact/BAO/Query.php', "<?php\n");
        },
        'which may override core classes',
      ],
      'missingPsr0' => [
        function (): void {
          $infoXml = file_get_contents('info.xml');
          $infoXml = str_replace('<psr0 prefix="CRM_" path="."/>', '', $infoXml);
          file_put_contents('info.xml', $infoXml);
        },
        'Extension has CRM/ folder but lacks <classloader><psr0',
      ],
      'relativeRequires' => [
        function (): void {
          file_put_contents('CRM/CivixIncpath/Custom.php', "<?php\nrequire_once 'CRM/Utils/File.php';\n");
        },
        'Extension contains relative require/include statements',
      ],
      'packagesDir' => [
        function (): void {
          mkdir('packages', 0777, TRUE);
        },
        'Extension contains packages/ directory',
      ],
    ];
  }

}
