<?php

namespace E2E;

use CRM\CivixBundle\Generator;
use CRM\CivixBundle\Test\CommandTester;
use CRM\CivixBundle\Test\SubProcessCommandTester;
use CRM\CivixBundle\Utils\Files;
use CRM\CivixBundle\Utils\Path;
use ProcessHelper\ProcessHelper as PH;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Add this to a test-class to define an E2E test with a new civix-style extension/project.
 *
 * Requirements:
 * - The consuming class MUST define a static property `$key` with the name of the extension.
 * - The runner MUST set an env-var CIVIX_WORKSPACE. This is a folder where extensions can be generated.
 *
 * Services:
 * - The consuming class WILL (by default) inherit `setUpBeforeClass()` and `tearDownAfterClass()`.
 * - The consuming class MAY use helpers like `getWorkspacePath()` nd `getExtPath()`
 * - The consuming class MAY use helpers like `civixGenerateModule()`, `civixGeneratePage()`, `civix($cmd)`, etc.
 */
trait CivixProjectTestTrait {

  /**
   * @var string
   */
  private static $origDir;

  public static function getKey(): string {
    if (!property_exists(static::class, 'key')) {
      throw new \RuntimeException(sprintf("Class %s does not have property \$key"));
    }
    return static::$key;
  }

  public static function setUpBeforeClass(): void {
    static::assertValidSetup();
    static::$origDir = getcwd();
  }

  public static function tearDownAfterClass(): void {
    chdir(static::$origDir);
    self::$origDir = NULL;
  }

  public static function assertValidSetup(): void {
    if (!property_exists(static::class, 'key')) {
      throw new \RuntimeException(sprintf("Class %s does not have property \$longName"));
    }

    if (empty(getenv('CIVIX_WORKSPACE'))) {
      throw new \RuntimeException('Undefined variable: CIVIX_WORKSPACE');
    }

    static::getWorkspacePath()->mkdir();
  }

  public static function getWorkspacePath(...$subpath): Path {
    $path = new Path(getenv('CIVIX_WORKSPACE'));
    return empty($subpath) ? $path : $path->path(...$subpath);
  }

  public static function getExtPath(...$subpath): Path {
    array_unshift($subpath, static::getKey());
    return static::getWorkspacePath(...$subpath);
  }

  public static function civix(string $subCommand): CommandTester {
    \Civix::reset();

    $isolation = getenv('CIVIX_TEST_ISOLATION') ?: 'on';

    switch ($isolation) {
      case 'on':
        if (getenv('CIVIX_TEST_BINARY')) {
          $baseCommand = [getenv('CIVIX_TEST_BINARY')];
        }
        else {
          $baseCommand = ['php', (dirname(__DIR__, 2) . '/bin/civix')];
        }
        $baseCommand[] = $subCommand;
        $baseCommand[] = '-v';
        return new SubProcessCommandTester($baseCommand);

      case 'off':
        $application = new \CRM\CivixBundle\Application();
        $command = $application->find($subCommand);
        return new class ($command) extends \Symfony\Component\Console\Tester\CommandTester implements CommandTester {

        };

      default:
        throw new \RuntimeException("Unrecognized value of CIVIX_TEST_BINARY. Specify on|off.");
    }
  }

  public function civixGenerateModule(string $key, array $options = []): CommandTester {
    $tester = static::civix('generate:module');
    $tester->execute($options + [
      'key' => $key,
      '--enable' => 'false',
    ]);
    $this->assertTesterOk($tester, 'Failed to generate module');
    return $tester;
  }

  public function civixGeneratePage(string $className, string $webPath): CommandTester {
    $tester = static::civix('generate:page');
    $tester->execute([
      '<ClassName>' => $className,
      '<web/path>' => $webPath,
    ]);
    $this->assertTesterOk($tester, 'Failed to generate page');
    return $tester;
  }

  public function civixGenerateEntity(string $entity, array $options = []): CommandTester {
    $tester = static::civix('generate:entity');
    $tester->execute(['<EntityName>' => $entity] + $options);
    $this->assertTesterOk($tester, 'Failed to generate entity');
    return $tester;
  }

  public function civixGenerateService(string $name, array $options = []): CommandTester {
    $tester = static::civix('generate:service');
    $tester->execute($options + ['name' => $name]);
    $this->assertTesterOk($tester, 'Failed to generate service');
    return $tester;
  }

  public function civixGenerateUpgrader(array $options = []): CommandTester {
    $tester = static::civix('generate:upgrader');
    $tester->execute($options);
    $this->assertTesterOk($tester, 'Failed to generate upgrader');
    return $tester;
  }

  /**
   * Get a value from "info.xml" by calling "civix info:get"
   *
   * @param string $xpath
   */
  public function civixInfoGet(string $xpath): CommandTester {
    $tester = static::civix('info:get');
    $tester->execute([
      '--xpath' => $xpath,
    ]);
    $this->assertTesterOk($tester, sprintf("Failed to get \"%s\"", $xpath));
    return $tester;
  }

  /**
   * Update the "info.xml" by calling "civix info:set"
   *
   * @param string $xpath
   * @param string $value
   */
  public function civixInfoSet(string $xpath, string $value): CommandTester {
    $tester = static::civix('info:set');
    $tester->execute([
      '--xpath' => $xpath,
      '--to' => $value,
    ]);
    $this->assertTesterOk($tester, sprintf("Failed to set \"%s\" to \"%s\"", $xpath, $value));
    return $tester;
  }

  /**
   * Update the mixin settings by calling `civix mixin`.
   *
   * @param array $options
   *  Ex: ['--disable-all' => TRUE]
   *  Ex: ['--enable' => 'foo@1']
   * @return \Symfony\Component\Console\Tester\CommandTester
   */
  public function civixMixin(array $options): CommandTester {
    $tester = static::civix('mixin');
    $tester->execute($options);
    $this->assertTesterOk($tester, sprintf("Failed to call \"civix mixin\" with options: %s", json_encode($options)));
    return $tester;
  }

  /**
   * Run the 'upgrade' command (non-interactively; all default choices).
   *
   * @param array $options
   * @return \Symfony\Component\Console\Tester\CommandTester
   */
  public function civixUpgrade(array $options = []): CommandTester {
    $tester = static::civix('upgrade');
    $tester->execute($options);
    $this->assertTesterOk($tester, sprintf("Failed to run upgrade (%s)", static::getKey()));
    return $tester;
  }

  /**
   * Get the upgrade-utility/helper.
   *
   * @return \CRM\CivixBundle\Generator
   */
  public function civixGeneratorHelper(): Generator {
    $input = new ArrayInput([]);
    $output = new StreamOutput(fopen('php://memory', 'w', FALSE));
    \Civix::ioStack()->push($input, $output);
    try {
      return \Civix::generator(static::getExtPath());
    }
    finally {
      \Civix::ioStack()->pop();
    }
  }

  /**
   * If a directory exists, remove it.
   *
   * @param string $dir
   */
  protected static function cleanDir($dir): void {
    PH::runOk(['if [ -d @DIR ]; then rm -rf @DIR ; fi', 'DIR' => $dir]);
  }

  protected function assertFileGlobs(array $globs): void {
    $globs = (array) $globs;
    $errors = [];
    foreach ($globs as $glob => $expectMatches) {
      $matches = (array) glob($glob);
      if ($expectMatches === TRUE && empty($matches)) {
        $errors[$glob] = "Expected matches for \"$glob\", but none were found.";
      }
      if ($expectMatches === FALSE && !empty($matches)) {
        $errors[$glob] = "Unexpected matches for \"$glob\": " . implode(' ', $matches);
      }
      if (is_int($expectMatches)) {
        $countMatches = count($matches);
        if ($countMatches != $expectMatches) {
          $errors[$glob] = "Expected to find {$expectMatches} matches for \"$glob\". Found  {$countMatches} matches (" . implode(' ', $matches) . ")";
        }
      }
    }
    $this->assertEquals([], $errors);
  }

  /**
   * Assert that various mixins are turned on or off.
   *
   * @param array $expectedMixins
   *   Ex: ['setting-php@1' => 'on']
   *   Valid statuses:
   *    - 'off' (mixin does not appear in 'info.xml' or 'mixins/*.php')
   *    - 'on' (mixin appears 'info.xml' but does not need a backport 'mixins/*.php')
   *    - 'on+backport' (mixin appears in both 'info.xml' and 'mixins/*.php')
   */
  protected function assertMixinStatuses(array $expectedMixins): void {
    $output = $this->civixInfoGet('mixins/mixin')->getDisplay();
    $lines = explode("\n", $output);

    $errors = [];
    foreach ($expectedMixins as $mixinPrefix => $expectedStatus) {
      $mixinRegex = ';^' . $mixinPrefix . ';';
      $mixinBackportsGlob = "mixin/{$mixinPrefix}.*.*.mixin.php";
      $mixinBackports = (array) glob($mixinBackportsGlob);

      switch ($expectedStatus) {
        case 'off':
          if (preg_grep($mixinRegex, $lines)) {
            $errors[] = "Unexpectedly found mixin $mixinPrefix";
          }
          if (count($mixinBackports) > 0) {
            $errors[] = "Found unnecessary backport files for $mixinPrefix: " . json_encode($mixinBackports);
          }
          break;

        case 'on':
          if (!preg_grep($mixinRegex, $lines)) {
            $errors[] = "Failed to find mixin $mixinPrefix";
          }
          if (count($mixinBackports) > 0) {
            $errors[] = "Found unnecessary backport files for $mixinPrefix: " . json_encode($mixinBackports);
          }
          break;

        case 'on+backport':
          if (!preg_grep($mixinRegex, $lines)) {
            $errors[] = "Failed to find mixin $mixinPrefix";
          }
          if (count($mixinBackports) < 1) {
            $errors[] = "Failed to find backport files for $mixinPrefix: $mixinBackportsGlob";
          }
          if (count($mixinBackports) > 1) {
            $errors[] = "Found excessive backport files for $mixinPrefix: " . json_encode($mixinBackports);
          }
          break;

        default:
          throw new \RuntimeException("Error: Malformed expectationat (status=$expectedStatus)");
      }

    }
    $this->assertEquals('', implode("\n", $errors));
  }

  /**
   * Get a snapshot of all files from the extension.
   *
   * Note: There is an intnernal whitelist of supported file-extensions (*.php, *.xml, *.js, *.css, etc).
   *
   * @return array
   *   List of files and their contents.
   *   Ex: ['info.xml' => '<extension key="foo.bar">...</extension>']
   * @throws \Exception
   */
  protected function getExtSnapshot(): array {
    $snapshot = [];
    $extPath = static::getExtPath();
    $files = array_merge(
      Files::findFiles($extPath, '*.css'),
      Files::findFiles($extPath, '*.html'),
      Files::findFiles($extPath, '*.js'),
      Files::findFiles($extPath, '*.md'),
      Files::findFiles($extPath, '*.php'),
      Files::findFiles($extPath, '*.tpl'),
      Files::findFiles($extPath, '*.txt'),
      Files::findFiles($extPath, '*.xml')
    );
    foreach ($files as $file) {
      $snapshot[Files::relativize($file, "$extPath/")] = file_get_contents($file);
    }
    ksort($snapshot);
    if (!isset($snapshot['info.xml']) || count($snapshot) < 2) {
      throw new \Exception("Error reading extension snapshot. The snapshot is missing basic data or has inconsistent filenames.");
    }
    return $snapshot;
  }

  /**
   * Assert that each item of $expectSubstrings appears in $actualText.
   * Strings must appear in the given order. Other text is ignored.
   *
   * @param string[] $expectSubstrings
   *   List of strings that should appear, in order of appearance.
   *   Ex: ['200', '400']
   * @param string $actualText
   *   Ex: "100\n200\n300\n400\n500\n"
   */
  protected function assertStringSequence(array $expectSubstrings, string $actualText) {
    $lastPos = 0;
    foreach ($expectSubstrings as $n => $expectLine) {
      $pos = strpos($actualText, $expectLine, $lastPos);
      $this->assertTrue($pos !== FALSE && $pos >= $lastPos, "Expect to find item #{$n} (\"$expectLine\")");
      $lastPos = $pos;
    }
    $this->assertTrue($lastPos > 0, 'Should have found multiple lines.');
  }

  /**
   * @return array
   *   [0 => InputInterface, 1 => OutputInterface]
   */
  protected function createInputOutput(array $argv = NULL): array {
    $input = new ArgvInput($argv);
    $input->setInteractive(FALSE);
    $output = new NullOutput();
    return [$input, $output];
  }

  protected function assertTesterOk(CommandTester $tester, string $message = NULL) {
    if ($tester->getStatusCode() !== 0) {
      $message = $message ?: 'Failed to run command';
      $command = is_callable([$tester, 'getCommandLine']) ? $tester->getCommandLine() : "(unknown)";
      throw new \RuntimeException(sprintf("(%s) %s\nCOMMMAND: %s\nOUTPUT:\n%s", static::getKey(), $message, $command, $tester->getDisplay(TRUE)));
    }
  }

}
