<?php

namespace E2E;

use CRM\CivixBundle\Application;
use CRM\CivixBundle\Upgrader;
use CRM\CivixBundle\Utils\Path;
use ProcessHelper\ProcessHelper as PH;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Tester\CommandTester;

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

  public static function civix(string $command): CommandTester {
    $application = new Application();
    $command = $application->find($command);
    return new CommandTester($command);
  }

  public function civixGenerateModule(string $key): CommandTester {
    $tester = static::civix('generate:module');
    $tester->execute([
      'key' => $key,
      '--enable' => 'false',
    ]);
    if ($tester->getStatusCode() !== 0) {
      throw new \RuntimeException(sprintf("Failed to generate module (%s)", $key));
    }
    return $tester;
  }

  public function civixGeneratePage(string $className, string $webPath): CommandTester {
    $tester = static::civix('generate:page');
    $tester->execute([
      '<ClassName>' => $className,
      '<web/path>' => $webPath,
    ]);
    if ($tester->getStatusCode() !== 0) {
      throw new \RuntimeException(sprintf("Failed to generate module (%s)", static::getKey()));
    }
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
    if ($tester->getStatusCode() !== 0) {
      throw new \RuntimeException(sprintf("Failed to set \"%s\" to \"%s\"", $xpath, $value));
    }
    return $tester;
  }

  public function civixUpgradeHelper(): Upgrader {
    $input = new ArrayInput([]);
    $output = new StreamOutput(fopen('php://memory', 'w', FALSE));
    return new Upgrader($input, $output, static::getExtPath());
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

}
