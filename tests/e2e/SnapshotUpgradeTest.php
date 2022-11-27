<?php

namespace E2E;

use CRM\CivixBundle\Utils\Path;
use ProcessHelper\ProcessHelper as PH;

/**
 * This is general sniff-test for running `civix upgrade`. The basic process:
 *
 * - Take a list of example extensions
 * - Extract the ZIP file (eg `tests/snapshot/org.example.civixsnapshot-v22.10.2-entity34.zip`)
 * - Run `civix upgrade`.
 * - Ensure that the extension can be installed.
 * - Check if the resources in the extension still work. (At time of writing, this is a small/limited set of checks.)
 *
 * The `tests/snapshot/*.zip` files are example extensions. Files are named as follows:
 *
 *   "{EXTENSION_KEY}-{CIVIX_VERSION}-{DATASET}.zip"
 *
 * Note that:
 *
 * - The "EXTENSION_KEy" is always 'org.example.civixsnapshot'.
 * - The "CIVIX_VERSION" identifies the *original generator* of the codebase.
 * - The "DATASET" describes what we put into the extension. For example:
 *      - 'empty': Just a basic extension that doesn't do anything.
 *      - 'qf': An extension with some QuickForm pages/forms.
 *      - 'entity3': An extension with an entity supporting APIv3.
 *      - 'entity34': An extension with an entity supporting APIv3 and APIv4.
 *      - 'kitchensink': An extension with a whole bunch of random things. (Varies based on the CIVIX_VERSION.)
 *
 * SnapshotUpgradeTest MUST run in an environment with `civibuild` and `cv`. It will use `civibuild restore`
 * to reinitialize the database.
 */
class SnapshotUpgradeTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  /**
   * Limit the list of snapshots to test. Useful for development/inspection of specific issues.
   *
   * This is a regular-expression against the filename.
   *
   * Alternatively, you may leave this NULL and set env-var SNAPSHOT_FILTER.
   */
  const SNAPSHOT_FILTER = NULL;
  // const SNAPSHOT_FILTER = '/v16.02/';              // All snapshots originating with v16.02.
  // const SNAPSHOT_FILTER = '/entity34/';            // All snapshots with APIv3+APIv4 entity.
  // const SNAPSHOT_FILTER = '/empty/';               // All empty snapshots.
  // const SNAPSHOT_FILTER = '/qf/';                  // All snapshots with QuickForm.
  // const SNAPSHOT_FILTER = '/v22.*entity34/';       // Snapshots originating in v22.* with APIv3+APIv4.

  /**
   * Name of the example extension.
   *
   * @var string
   */
  public static $key = 'org.example.civixsnapshot';

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
  }

  public function getSnapshotsAll(): array {
    return $this->findSnapshots('org.example.civixsnapshot-*.zip');
  }

  /**
   * @param string $snapshot
   * @dataProvider getSnapshotsAll
   */
  public function testSnapshot(string $snapshot) {
    $this->setupSnapshot($snapshot);

    $class = new \ReflectionClass($this);
    foreach ($class->getMethods() as $method) {
      /** @var \ReflectionMethod $method */
      if (preg_match('/^checkSnapshot_.*/', $method->getName())) {
        $method->invoke($this, $snapshot);
      }
    }
  }

  public function checkSnapshot_common(string $snapshot) {
    $getName = PH::runOk('cv ev "echo CRM_Civixsnapshot_ExtensionUtil::LONG_NAME";');
    $this->assertEquals('org.example.civixsnapshot', trim($getName->getOutput()));
  }

  public function checkSnapshot_entity3(string $snapshot): void {
    if (!preg_match(';-entity3.zip;', $snapshot)) {
      return;
    }

    $entity = 'MyEntityThree';

    $getFields3 = PH::runOK("cv api3 $entity.getfields --out=json");
    $parsed3 = json_decode($getFields3->getOutput(), TRUE);
    $descriptions3 = array_column($parsed3['values'], 'description');
    $this->assertTrue(in_array("Unique $entity ID", $descriptions3), "$entity.id should have APIv3 description. Actual metadata response was: " . $getFields3->getOutput());
    $this->assertTrue(in_array('FK to Contact', $descriptions3), "$entity.contact_id should have APIv3 description. Actual metadata response was: " . $getFields3->getOutput());
  }

  public function checkSnapshot_entity34(string $snapshot): void {
    if (!preg_match(';-entity34.zip;', $snapshot)) {
      return;
    }

    $entity = 'MyEntityThreeFour';

    $getFields3 = PH::runOK("cv api3 $entity.getfields --out=json");
    $parsed3 = json_decode($getFields3->getOutput(), TRUE);
    $descriptions3 = array_column($parsed3['values'], 'description');
    $this->assertTrue(in_array("Unique $entity ID", $descriptions3), "$entity.id should have APIv3 description. Actual metadata response was: " . $getFields3->getOutput());
    $this->assertTrue(in_array('FK to Contact', $descriptions3), "$entity.contact_id should have APIv3 description. Actual metadata response was: " . $getFields3->getOutput());

    $getFields4 = PH::runOK("cv api4 $entity.getFields --out=json");
    $parsed4 = json_decode($getFields4->getOutput(), TRUE);
    $descriptions4 = array_column($parsed4, 'description');
    $this->assertTrue(in_array("Unique $entity ID", $descriptions4), "$entity.id should have APIv4 description. Actual metadata response was: ");
    $this->assertTrue(in_array('FK to Contact', $descriptions4), "$entity.contact_id should have APIv4 description. Actual metadata response was: ");
  }

  public function checkSnapshot_qf(string $snapshot): void {
    if (!preg_match(';-qf.zip;', $snapshot)) {
      return;
    }

    $getPage = PH::runOK('cv api4 Route.get +w path=civicrm/my-page +s page_callback');
    $this->assertTrue((bool) preg_match('/CRM_Civixsnapshot_Page_MyPage/', $getPage->getOutput()), 'Route should be registered');

    $classExists = PH::runOk('cv ev \'echo class_exists(CRM_Civixsnapshot_Page_MyPage::class) ? "found" : "missing";\'');
    $this->assertTrue((bool) preg_match('/^found/', $classExists->getOutput()), 'Class should be loadable/parsable.');

    // FIXME: Send an actual web-request... or maybe add a test...
    // PH::runOk('cv en authx && cv curl --user=demo --login civicrm/my-page');
  }

  /**
   * Extract, upgrade, and install the $snapshot in a clean environment.
   *
   * @param string $snapshot
   */
  protected function setupSnapshot(string $snapshot) {
    PH::runOk('civibuild restore');
    PH::runOk('unzip ' . escapeshellarg($this->getSnapshotPath($snapshot)));
    chdir(static::getKey());
    $upgrade = $this->civixUpgrade()->getDisplay(TRUE);
    $this->assertStringSequence(['Incremental upgrades', 'General upgrade'], $upgrade);
    PH::runOk('cv en civixsnapshot');
  }

  public static function getSnapshotPath(...$subpath): Path {
    $path = new Path(dirname(__DIR__));
    array_unshift($subpath, 'snapshots');
    return empty($subpath) ? $path : $path->path(...$subpath);
  }

  public function findSnapshots(string $glob): array {
    $r = [];
    if (static::SNAPSHOT_FILTER && getenv('SNAPSHOT_FILTER')) {
      throw new \RuntimeException('Error: SNAPSHOT_FILTER has been set twice!');
    }
    $filter = static::SNAPSHOT_FILTER ?: getenv('SNAPSHOT_FILTER') ?: ';.;';
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
