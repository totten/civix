<?php
namespace E2E;

use CRM\CivixBundle\Utils\Path;
use ProcessHelper\ProcessHelper as PH;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Helper for writing tests with the files in "tests/snapshots/*".
 *
 * Each folder may contain:
 *
 * - "original.zip" (committed/long term file)
 * - "upgrade" (temp output from running the test, if SNAPSHOT_SAVE is on)
 * - "upgrade.log" (temp output from running the test, if SNAPSHOT_SAVE is on)
 * - "upgrade.diff" (temp output from running the test, if SNAPSHOT_SAVE is on)
 */
trait CivixSnapshotUpgradeTestTrait {

  /**
   * Name of the current snapshot file.
   *
   * @var string
   */
  protected $snapshot;

  /**
   * Console output from running `civix upgrade` against the current snapshot file.
   *
   * @var string|null
   */
  protected $upgradeLog;

  /**
   * Extract, upgrade, and install the $snapshot in a clean environment.
   *
   * A reference to the newly upgrade snapshot will be stored in `$this->snapshot` and `$this->upgradeLog`.
   *
   * @param string $snapshot
   */
  protected function setupSnapshot(string $snapshot) {
    $fs = new Filesystem();
    $snapshotDir = $this->getSnapshotPath($snapshot);
    $this->snapshot = $snapshot;

    $fs->remove(["$snapshotDir/upgrade", "$snapshotDir/upgrade.diff", "$snapshotDir/upgrade.log"]);
    PH::runOk('civibuild restore');

    PH::runOk('unzip ' . escapeshellarg("$snapshotDir/original.zip"));
    chdir(static::getKey());
    $upgrade = $this->civixUpgrade();
    $this->upgradeLog = $upgrade->getDisplay(TRUE);

    if ($this->resolveConstant('SNAPSHOT_SAVE', FALSE)) {
      file_put_contents("$snapshotDir/upgrade.log", $this->upgradeLog);
      $fs->mirror('.', "$snapshotDir/upgrade");
      PH::runOk(sprintf('zipdiff %s/original.zip %s/upgrade > %s/upgrade.diff', escapeshellarg($snapshotDir), escapeshellarg($snapshotDir), escapeshellarg($snapshotDir)));
    }

    $this->assertStringSequence(['Incremental upgrades', 'General upgrade'], $this->upgradeLog);
    PH::runOk('cv en civixsnapshot');
  }

  public static function getSnapshotPath(...$subpath): Path {
    $path = new Path(dirname(__DIR__));
    array_unshift($subpath, 'snapshots');
    return empty($subpath) ? $path : $path->path(...$subpath);
  }

  /**
   * Check the logical name of the scenario.
   *
   * Recall file-name formula: "{EXTENSION_KEY}-{CIVIX_VERSION}-{SCENARIO}"
   *
   * @param string $type
   *   Ex: 'entity34'
   * @return bool
   *   TRUE for a file like "org.example.civixsnapshot-v22.05.0-entity34",
   *   FALSE for a file like "org.example.civixsnapshot-v22.05.0-qf",
   */
  public function isScenario(string $type): bool {
    [, , $actualType] = explode('-', $this->snapshot);
    return $actualType === $type;
  }

  /**
   * Check if the original civix version is greater-than or less-than some number.
   *
   * Recall file-name formula: "{EXTENSION_KEY}-{CIVIX_VERSION}-{SCENARIO}"
   *
   * @param string $operator
   *   Ex: '>'
   * @param string $expectCivixVer
   *   Ex: '22.05.0'
   * @return bool
   *   TRUE for a file like "org.example.civixsnapshot-v22.06.0-empty",
   *   FALSE for a file like "org.example.civixsnapshot-v21.05.0-empty",
   */
  public function wasCivixVersion(string $operator, string $expectCivixVer): bool {
    [, $actualCivixVer] = explode('-', $this->snapshot);
    $actualCivixVer = str_replace('v', '', $actualCivixVer);
    $expectCivixVer = str_replace('v', '', $expectCivixVer);
    return version_compare($actualCivixVer, $expectCivixVer, $operator);
  }

  /**
   * @param string $glob
   *   Path to some example files (which may or may not exist).
   * @return bool
   *   TRUE if the current scenario is "kitchensink" AND the $glob files exist.
   */
  public function isKitchenSinkWith(string $glob): bool {
    return $this->isScenario('kitchensink') && !empty(glob($glob));
  }

  public function findSnapshots(string $glob): array {
    $r = [];
    $filter = $this->resolveConstant('SNAPSHOT_FILTER', ';.;');
    $files = glob($this->getSnapshotPath($glob));
    foreach ($files as $file) {
      if (preg_match($filter, basename($file)) && is_dir($file)) {
        $key = preg_replace(';^org\.example\.(.*)$;', '$1', basename($file));
        $r[$key] = [basename($file)];
      }
    }
    return $r;
  }

  protected function resolveConstant(string $name, $default = NULL) {
    $constName = get_class($this) . '::' . $name;
    $filterConstant = defined($constName) ? constant($constName) : NULL;
    $filterEnv = getenv($name);
    // if ($filterConstant && $filterEnv) {
    //  throw new \RuntimeException("Error: $name has been set twice!");
    //}
    // return $filterConstant ?: $filterEnv ?: $default;
    return $filterEnv ?: $filterConstant ?: $default;
  }

}
