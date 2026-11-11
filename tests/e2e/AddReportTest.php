<?php

namespace E2E;

use ProcessHelper\ProcessHelper;

class AddReportTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_addreport';

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
    $this->civixGenerateModule(static::getKey());
    chdir(static::getKey());
  }

  public function testAddReport(): void {
    $this->civixGenerateReport('MyReport', 'CiviContribute');

    $this->assertFileGlobs([
      'managed/MyReport.mgd.php' => 1,
      'CRM/CivixAddreport/Form/Report/MyReport.php' => 1,
      'templates/CRM/CivixAddreport/Form/Report/MyReport.tpl' => 1,
    ]);

    ProcessHelper::runOk('php -l managed/MyReport.mgd.php');
    $this->assertStringSequence([
      "use CRM_CivixAddreport_ExtensionUtil as E;",
      "// Managed entity for the MyReport ReportTemplate.",
      "'entity' => 'OptionValue'",
      "'version' => 4",
      "'option_group_id.name' => 'report_template'",
      "'label' => E::ts('MyReport')",
      "'description' => E::ts('MyReport (civix_addreport)')",
      "'name' => 'CRM_CivixAddreport_Form_Report_MyReport'",
      "'value' => 'civix_addreport/myreport'",
      "'component_id.name' => 'CiviContribute'",
      "'match' => [",
      "'option_group_id',",
      "'name',",
    ], file_get_contents('managed/MyReport.mgd.php'));

    // Test report with 'null' component and custom webPath
    $this->civixGenerateReport('CustomReport', 'null', ['--webPath' => 'civicrm/report/custom/path']);
    $customMgd = file_get_contents('managed/CustomReport.mgd.php');
    $this->assertStringContainsString("'value' => 'custom/path'", $customMgd);
    $this->assertStringNotContainsString('component_id.name', $customMgd);
  }

}
