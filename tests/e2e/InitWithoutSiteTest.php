<?php

namespace E2E;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Run `generate:module` in a directory that belongs to no CiviCRM site.
 *
 * This always uses a subprocess: an in-process run may inherit a booted site.
 */
class InitWithoutSiteTest extends \PHPUnit\Framework\TestCase {

  /**
   * @var string
   */
  protected $dir;

  public function setUp(): void {
    $this->dir = sys_get_temp_dir() . '/civix-nosite-' . getmypid();
    (new Filesystem())->remove($this->dir);
    mkdir($this->dir);
  }

  public function tearDown(): void {
    (new Filesystem())->remove($this->dir);
  }

  public function testExplicitCompatibilityWithoutEnable(): void {
    $p = $this->runGenerateModule(['--enable=no', '--compatibility=6.0']);
    $this->assertEquals(0, $p->getExitCode(), $p->getOutput() . $p->getErrorOutput());
    $this->assertStringContainsString('<ver>6.0</ver>', file_get_contents($this->dir . '/civix_nosite/info.xml'));
    $this->assertFileExists($this->dir . '/civix_nosite/civix_nosite.civix.php');
  }

  public function testCurrentCompatibilityNeedsSite(): void {
    $p = $this->runGenerateModule(['--enable=no']);
    $this->assertNotEquals(0, $p->getExitCode(), $p->getOutput());
    $this->assertDirectoryDoesNotExist($this->dir . '/civix_nosite');
  }

  protected function runGenerateModule(array $options): Process {
    $command = getenv('CIVIX_TEST_BINARY') ? [getenv('CIVIX_TEST_BINARY')] : ['php', dirname(__DIR__, 2) . '/bin/civix'];
    $command = array_merge($command, ['generate:module', 'civix_nosite', '--author=Test', '--email=test@example.org'], $options);
    $p = new Process($command, $this->dir, ['CIVICRM_SETTINGS' => FALSE, 'CIVICRM_BOOT' => FALSE]);
    $p->run();
    return $p;
  }

}
