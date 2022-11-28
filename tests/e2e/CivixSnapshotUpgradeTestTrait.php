<?php
namespace E2E;

use CRM\CivixBundle\Utils\Path;
use ProcessHelper\ProcessHelper as PH;

/**
 * Helper for writing tests with the files in "tests/snapshots/*.zip".
 *
 * Generally, it is expected that each test-run will handle one "*.zip" file. You may need
 * to lookup a reference to the "*.zip" file and do some assertions about it.
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
    $this->snapshot = $snapshot;
    PH::runOk('civibuild restore');
    PH::runOk('unzip ' . escapeshellarg($this->getSnapshotPath($snapshot)));
    chdir(static::getKey());
    $this->upgradeLog = $this->civixUpgrade()->getDisplay(TRUE);
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
   * Recall file-name formula: "{EXTENSION_KEY}-{CIVIX_VERSION}-{SCENARIO}.zip"
   *
   * @param string $type
   *   Ex: 'entity34'
   * @return bool
   *   TRUE for a file like "org.example.civixsnapshot-v22.05.0-entity34.zip",
   *   FALSE for a file like "org.example.civixsnapshot-v22.05.0-qf.zip",
   */
  public function isScenario(string $type): bool {
    [, , $actualType] = explode('-', $this->snapshot);
    $actualType = str_replace('.zip', '', $actualType);
    return $actualType === $type;
  }

  /**
   * Check if the original civix version is greater-than or less-than some number.
   *
   * Recall file-name formula: "{EXTENSION_KEY}-{CIVIX_VERSION}-{SCENARIO}.zip"
   *
   * @param string $operator
   *   Ex: '>'
   * @param string $expectCivixVer
   *   Ex: '22.05.0'
   * @return bool
   *   TRUE for a file like "org.example.civixsnapshot-v22.06.0-empty.zip",
   *   FALSE for a file like "org.example.civixsnapshot-v21.05.0-empty.zip",
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

    $filterConstantName = static::class . '::SNAPSHOT_FILTER';
    $filterConstant = defined($filterConstantName) ? constant($filterConstantName) : NULL;
    $filterEnv = getenv('SNAPSHOT_FILTER');
    if ($filterConstant && $filterEnv) {
      throw new \RuntimeException('Error: SNAPSHOT_FILTER has been set twice!');
    }

    $filter = $filterConstant ?: $filterEnv ?: ';.;';
    $files = glob($this->getSnapshotPath($glob));
    foreach ($files as $file) {
      if (preg_match($filter, basename($file))) {
        $key = preg_replace(';^org\.example\.(.*)\.zip$;', '$1', basename($file));
        $r[$key] = [basename($file)];
      }
    }
    return $r;
  }

}
