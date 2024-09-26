<?php

namespace E2E;

use CRM\CivixBundle\RunMethodsTrait;
use ProcessHelper\ProcessHelper as PH;

/**
 * This is general sniff-test for running `civix upgrade`. It takes a list of example
 * extensions (eg `tests/snapshot/*.zip`) and runs each. Specifically:
 *
 * - Extract the ZIP file.
 * - Run `civix upgrade`.
 * - Enable the upgraded extension.
 * - Run each of the `checkSnapshot_*()`  methods.
 *
 * The `tests/snapshot/*.zip` files are extensions (previously generated by civix).
 *
 * - Example: "tests/snapshot/org.example.civixsnapshot-v22.10.2-entity34.zip"
 * - Formula: "tests/snapshot/{EXTENSION_KEY}-{CIVIX_VERSION}-{SCENARIO}.zip"
 *
 * Note that:
 *
 * - The "EXTENSION_KEY" is always 'org.example.civixsnapshot'.
 * - The "CIVIX_VERSION" identifies the *original generator* of the snapshot.
 * - The "SCENARIO" describes what we put into the extension. For example:
 *      - 'empty': Just a basic extension that doesn't do anything.
 *      - 'qf': An extension with some QuickForm pages/forms.
 *      - 'entity3': An extension with an entity supporting APIv3.
 *      - 'entity34': An extension with an entity supporting APIv3 and APIv4.
 *      - 'entity4': An extension with an entity supporting APIv4.
 *      - 'kitchensink': An extension with a bunch of random things. (Varies based on the CIVIX_VERSION.)
 *      - 'svc': An extension with a service-object.
 *      - (NOTE: For a more detailed sketch of each scenario, see `tests/make-snapshots.sh`.)
 *
 * SnapshotUpgradeTest MUST run in an environment with `civibuild` and `cv`. It will use `civibuild restore`
 * to reinitialize the database.
 */
class SnapshotUpgradeTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;
  use CivixSnapshotUpgradeTestTrait;
  use RunMethodsTrait;

  /**
   * Limit the list of snapshots to test. Useful for development/inspection of specific issues.
   *
   * This is a regular-expression against the filename.
   *
   * Alternatively, you may set env-var SNAPSHOT_FILTER.
   */
  // const SNAPSHOT_FILTER = NULL;
  // const SNAPSHOT_FILTER = '/v16.02/';              // All snapshots originating with v16.02.
  // const SNAPSHOT_FILTER = '/entity34/';            // All snapshots with APIv3+APIv4 entity.
  // const SNAPSHOT_FILTER = '/empty/';               // All empty snapshots.
  // const SNAPSHOT_FILTER = '/qf/';                  // All snapshots with QuickForm.
  // const SNAPSHOT_FILTER = '/v22.*entity34/';       // Snapshots originating in v22.* with APIv3+APIv4.

  /**
   * Should we retain extra copies of the upgraded code?
   *
   * Alternatively, you may set env-var SNAPSHOT_SAVE.
   */
  // const SNAPSHOT_SAVE = FALSE;                     // Default
  // const SNAPSHOT_SAVE = TRUE;

  /**
   * Name of the example extension.
   *
   * @var string
   */
  public static $key = 'org.example.civixsnapshot';

  public function getSnapshots(): array {
    return $this->findSnapshots('org.example.civixsnapshot-*');
  }

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
  }

  /**
   * @param string $snapshot
   * @dataProvider getSnapshots
   * @throws \ReflectionException
   */
  public function testSnapshot(string $snapshot): void {
    $this->setupSnapshot($snapshot);
    [$liveChecks, $skipChecks] = $this->runMethods('/^checkSnapshot_.*/');

    if (getenv('DEBUG') >= 1) {
      printf("%s(%s) executed these checks: %s\n", __FUNCTION__, json_encode($snapshot), json_encode(array_keys($liveChecks)));
      printf("%s(%s) skipped these checks: %s\n", __FUNCTION__, json_encode($snapshot), json_encode(array_keys($skipChecks)));
    }

    $minChecks = $this->isScenario('empty') ? 1 : 2;
    $this->assertGreaterThanOrEqual($minChecks, count($liveChecks), "Check for $snapshot should have at least $minChecks affirmative checks.");
  }

  /**
   * All snapshots will have big E support ("CRM_*_ExtensionUtil"). We can make sure that it's loadable.
   */
  public function checkSnapshot_common() {
    $getName = PH::runOk('cv ev "echo CRM_Civixsnapshot_ExtensionUtil::LONG_NAME";');
    $this->assertEquals('org.example.civixsnapshot', trim($getName->getOutput()));
  }

  /**
   * The "*-entity3.zip" snapshots include an entity ("MyEntityThree") with APIv3 support.
   * This also appears in some kitchen-sink builds.
   */
  public function checkSnapshot_entity3(): void {
    $this->runsIf($this->isScenario('entity3') || $this->isKitchenSinkWith('xml/schema/CRM/Civixsnapshot/MyEntityThree.xml'));

    $entity = 'MyEntityThree';

    $getFields3 = PH::runOK("cv api3 $entity.getfields --out=json");
    $parsed3 = json_decode($getFields3->getOutput(), TRUE);
    $descriptions3 = array_column($parsed3['values'], 'description');
    $this->assertTrue(in_array("Unique $entity ID", $descriptions3), "$entity.id should have APIv3 description. Actual metadata response was: " . $getFields3->getOutput());
    $this->assertTrue(in_array('FK to Contact', $descriptions3), "$entity.contact_id should have APIv3 description. Actual metadata response was: " . $getFields3->getOutput());
  }

  /**
   * The "*-entity34.zip" snapshots include an entity ("MyEntityThreeFour") with APIv3+APIv4 support.
   * This also appears in some kitchen-sink builds.
   */
  public function checkSnapshot_entity34(): void {
    $this->runsIf($this->isScenario('entity34') || $this->isKitchenSinkWith('xml/schema/CRM/Civixsnapshot/MyEntityThreeFour.xml'));

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

  /**
   * The "*-entity4.zip" snapshots include an entity ("MyEntityFour") with APIv4 support.
   * This also appears in some kitchen-sink builds.
   */
  public function checkSnapshot_entity4(): void {
    $this->runsIf($this->isScenario('entity4') || $this->isKitchenSinkWith('xml/schema/CRM/Civixsnapshot/MyEntityFour.xml'));

    $entity = 'MyEntityFour';

    $getFields4 = PH::runOK("cv api4 $entity.getFields --out=json");
    $parsed4 = json_decode($getFields4->getOutput(), TRUE);
    $descriptions4 = array_column($parsed4, 'description');
    $this->assertTrue(in_array("Unique $entity ID", $descriptions4), "$entity.id should have APIv4 description. Actual metadata response was: ");
    $this->assertTrue(in_array('FK to Contact', $descriptions4), "$entity.contact_id should have APIv4 description. Actual metadata response was: ");
  }

  /**
   * The "*-qf.zip" snapshots include a traditional page+form (eg "civicrm/my-page").
   * This also appears in some kitchen-sink builds.
   */
  public function checkSnapshot_qf(): void {
    $this->runsIf($this->isScenario('qf') || $this->isKitchenSinkWith('CRM/Civixsnapshot/Page/MyPage.php'));

    $getPage = PH::runOK('cv api4 Route.get +w path=civicrm/my-page +s page_callback');
    $this->assertTrue((bool) preg_match('/CRM_Civixsnapshot_Page_MyPage/', $getPage->getOutput()), 'Route should be registered');

    $classExists = PH::runOk('cv ev \'echo class_exists(CRM_Civixsnapshot_Page_MyPage::class) ? "found" : "missing";\'');
    $this->assertTrue((bool) preg_match('/^found/', $classExists->getOutput()), 'Class should be loadable/parsable.');

    $getPage = PH::runOK('cv api4 Route.get +w path=civicrm/my-form +s page_callback');
    $this->assertTrue((bool) preg_match('/CRM_Civixsnapshot_Form_MyForm/', $getPage->getOutput()), 'Route should be registered');

    $classExists = PH::runOk('cv ev \'echo class_exists(CRM_Civixsnapshot_Form_MyForm::class) ? "found" : "missing";\'');
    $this->assertTrue((bool) preg_match('/^found/', $classExists->getOutput()), 'Class should be loadable/parsable.');

    $httpGet = PH::runOk('cv en authx && cv http -LU admin civicrm/my-page');
    $this->assertMatchesRegularExpression(';The current time is;', $httpGet->getOutput());
  }

  public function checkSnapshot_svc(): void {
    $this->runsIf($this->isScenario('svc') || $this->isKitchenSinkWith('Civi/Civixsnapshot/Some/Thing.php'));

    $getSystem = PH::runOK('cv api3 System.get --out=json');
    $system = json_decode($getSystem->getOutput(), TRUE);
    if (!version_compare($system['values'][0]['version'], '5.55', '>=')) {
      return;
    }

    $getServices = PH::runOK("cv service some.thing --out=json");
    $parsed = json_decode($getServices->getOutput(), TRUE);
    $this->assertEquals('some.thing', $parsed[0]['service'], 'Expected to find service name');
    $this->assertEquals('Civi\\Civixsnapshot\\Some\\Thing', $parsed[0]['class'], 'Expected to find class name');
    $this->assertTrue((bool) preg_match(';tag.event_subscriber;', $parsed[0]['extras']), 'Failed to find tag.event_subscriber in description');
  }

}
