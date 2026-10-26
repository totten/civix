<?php

namespace E2E;

use ProcessHelper\ProcessHelper;

class AddManagedEntityTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_exportmgd';

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
    $this->civixGenerateModule(static::getKey());
    chdir(static::getKey());

    $this->assertFileGlobs([
      'info.xml' => 1,
      'civix_exportmgd.php' => 1,
      'civix_exportmgd.civix.php' => 1,
    ]);
    $this->civixMixin(['--disable-all' => TRUE]);
  }

  public function testAddMgd(): void {
    $this->assertMixinStatuses(['mgd-php@2' => 'off']);
    $this->assertFileGlobs(['managed/OptionGroup_preferred_communication_method.mgd.php' => 0]);

    $tester = static::civix('export');
    $tester->execute(['<EntityName>' => 'OptionGroup', '<EntityId>' => 1, '--no-interaction' => TRUE]);
    if ($tester->getStatusCode() !== 0) {
      throw new \RuntimeException(sprintf("Failed to generate mgd (%s)", static::getKey()));
    }

    $compatVer = trim($this->civixInfoGet('compatibility/ver')->getDisplay());
    $this->assertMixinStatuses([
      'mgd-php@2' => version_compare($compatVer, '6.9.alpha1', '>=') ? 'on' : 'on+backport',
    ]);
    $this->assertFileGlobs(['managed/OptionGroup_preferred_communication_method.mgd.php' => 1]);

    ProcessHelper::runOk('php -l managed/OptionGroup_preferred_communication_method.mgd.php');
    $expectPhrases = [
      "use CRM_CivixExportmgd_ExtensionUtil as E;",
      "'title' => E::ts('Preferred Communication Method')",
      "'option_group_id.name' => 'preferred_communication_method'",
      "'label' => E::ts('Phone')",
      "'value' => '1'",
      "'label' => E::ts('Email')",
      "'value' => '2'",
    ];
    $this->assertStringSequence($expectPhrases, file_get_contents('managed/OptionGroup_preferred_communication_method.mgd.php'));
  }

  public function testAddMgdInteractiveRename(): void {
    $create = ProcessHelper::runOk('cv api4 OptionGroup.create +v name=civix_test_rename +v title="Civix Test Rename" --out=json');
    $created = json_decode($create->getOutput(), TRUE);
    $id = $created[0]['id'];

    try {
      $tester = static::civix('export');
      $tester->setInputs(['y']);
      $tester->execute(['<EntityName>' => 'OptionGroup', '<EntityId>' => $id]);
      if ($tester->getStatusCode() !== 0) {
        throw new \RuntimeException(sprintf("Failed to generate mgd (%s)", static::getKey()));
      }

      $this->assertFileGlobs(['managed/OptionGroup_civix_exportmgd_civix_test_rename.mgd.php' => 1]);

      $get = ProcessHelper::runOk("cv api4 OptionGroup.get +w id=$id --out=json");
      $got = json_decode($get->getOutput(), TRUE);
      $this->assertEquals('civix_exportmgd_civix_test_rename', $got[0]['name']);
    }
    finally {
      ProcessHelper::runOk("cv api4 OptionGroup.delete +w id=$id");
    }
  }

  public function testAddMgdInteractiveDecline(): void {
    $create = ProcessHelper::runOk('cv api4 OptionGroup.create +v name=civix_test_decline +v title="Civix Test Decline" --out=json');
    $created = json_decode($create->getOutput(), TRUE);
    $id = $created[0]['id'];

    try {
      $tester = static::civix('export');
      $tester->setInputs(['n']);
      $tester->execute(['<EntityName>' => 'OptionGroup', '<EntityId>' => $id]);
      if ($tester->getStatusCode() !== 0) {
        throw new \RuntimeException(sprintf("Failed to generate mgd (%s)", static::getKey()));
      }

      $this->assertFileGlobs(['managed/OptionGroup_civix_test_decline.mgd.php' => 1]);

      $get = ProcessHelper::runOk("cv api4 OptionGroup.get +w id=$id --out=json");
      $got = json_decode($get->getOutput(), TRUE);
      $this->assertEquals('civix_test_decline', $got[0]['name']);
    }
    finally {
      ProcessHelper::runOk("cv api4 OptionGroup.delete +w id=$id");
    }
  }

  public function testAddMgdInteractiveAlreadyPrefixed(): void {
    $create = ProcessHelper::runOk('cv api4 OptionGroup.create +v name=civix_exportmgd_already_has_it +v title="Civix Test Already" --out=json');
    $created = json_decode($create->getOutput(), TRUE);
    $id = $created[0]['id'];

    try {
      $tester = static::civix('export');
      $tester->execute(['<EntityName>' => 'OptionGroup', '<EntityId>' => $id]);
      if ($tester->getStatusCode() !== 0) {
        throw new \RuntimeException(sprintf("Failed to generate mgd (%s)", static::getKey()));
      }

      $this->assertFileGlobs(['managed/OptionGroup_civix_exportmgd_already_has_it.mgd.php' => 1]);

      $get = ProcessHelper::runOk("cv api4 OptionGroup.get +w id=$id --out=json");
      $got = json_decode($get->getOutput(), TRUE);
      $this->assertEquals('civix_exportmgd_already_has_it', $got[0]['name']);
    }
    finally {
      ProcessHelper::runOk("cv api4 OptionGroup.delete +w id=$id");
    }
  }

  public function testAddMgdMungeMaxlength(): void {
    // 55 char name; civix_exportmgd (16) + _ (1) + 55 = 72 > 64
    $name = 'test_very_long_name_that_exceeds_maxlength_when_prefixed';
    $create = ProcessHelper::runOk("cv api4 OptionGroup.create +v name=$name +v title=\"Civix Long Name\" --out=json");
    $created = json_decode($create->getOutput(), TRUE);
    $id = $created[0]['id'];

    try {
      $tester = static::civix('export');
      $tester->setInputs(['y']);
      $tester->execute(['<EntityName>' => 'OptionGroup', '<EntityId>' => $id]);
      if ($tester->getStatusCode() !== 0) {
        throw new \RuntimeException(sprintf("Failed to generate mgd (%s)", static::getKey()));
      }

      $get = ProcessHelper::runOk("cv api4 OptionGroup.get +w id=$id --out=json");
      $got = json_decode($get->getOutput(), TRUE);
      $expectedName = substr("civix_exportmgd_$name", 0, 64);
      $this->assertEquals(64, strlen($got[0]['name']));
      $this->assertEquals($expectedName, $got[0]['name']);
      $this->assertFileGlobs(["managed/OptionGroup_$expectedName.mgd.php" => 1]);
    }
    finally {
      ProcessHelper::runOk("cv api4 OptionGroup.delete +w id=$id");
    }
  }

  public function testExportAfformInteractiveRename(): void {
    $create = ProcessHelper::runOk('cv api4 Afform.create +v name=afformCustomTest +v title="Custom Test Form" +v layout="<div>hello</div>" --out=json');
    $created = json_decode($create->getOutput(), TRUE);
    $name = $created[0]['name'];

    try {
      $tester = static::civix('export');
      $tester->setInputs(['y']);
      $tester->execute(['<EntityName>' => 'Afform', '<EntityId>' => $name]);
      if ($tester->getStatusCode() !== 0) {
        throw new \RuntimeException(sprintf("Failed to export afform (%s)", static::getKey()));
      }

      $this->assertFileGlobs([
        'ang/afform_civix_exportmgd_CustomTest.aff.html' => 1,
        'ang/afform_civix_exportmgd_CustomTest.aff.php' => 1,
      ]);

      $get = ProcessHelper::runOk('cv api4 Afform.get +w name=afform_civix_exportmgd_CustomTest --out=json');
      $got = json_decode($get->getOutput(), TRUE);
      $this->assertNotEmpty($got);
    }
    finally {
      ProcessHelper::runOk('cv api4 Afform.revert +w name=afformCustomTest');
      ProcessHelper::runOk('cv api4 Afform.revert +w name=afform_civix_exportmgd_CustomTest');
    }
  }

  public function testExportAfformWithEmbeddedSavedSearch(): void {
    $ss = ProcessHelper::runOk('cv api4 SavedSearch.create +v name=custom_search_for_afform +v label="Custom Search For Afform" +v api_entity=Contact --out=json');
    $ssCreated = json_decode($ss->getOutput(), TRUE);
    $ssId = $ssCreated[0]['id'];

    $layout = '<div af-fieldset=""><crm-search-display-table search-name="custom_search_for_afform" display-name="Table"></crm-search-display-table></div>';
    $create = ProcessHelper::runOk('cv api4 Afform.create +v name=afsearchCustomSearch +v title="Custom Search Form" +v layout=' . escapeshellarg($layout) . ' --out=json');
    $created = json_decode($create->getOutput(), TRUE);
    $name = $created[0]['name'];

    try {
      $tester = static::civix('export');
      // Input y for afform rename, y for saved search rename
      $tester->setInputs(['y', 'y']);
      $tester->execute(['<EntityName>' => 'Afform', '<EntityId>' => $name]);
      if ($tester->getStatusCode() !== 0) {
        throw new \RuntimeException(sprintf("Failed to export afform (%s)", static::getKey()));
      }

      $this->assertFileGlobs([
        'ang/afsearch_civix_exportmgd_CustomSearch.aff.html' => 1,
        'ang/afsearch_civix_exportmgd_CustomSearch.aff.php' => 1,
        'managed/SavedSearch_civix_exportmgd_custom_search_for_afform.mgd.php' => 1,
      ]);

      $html = file_get_contents('ang/afsearch_civix_exportmgd_CustomSearch.aff.html');
      $this->assertStringContainsString('search-name="civix_exportmgd_custom_search_for_afform"', $html);
      $this->assertStringNotContainsString('search-name="custom_search_for_afform"', $html);
    }
    finally {
      ProcessHelper::runOk('cv api4 Afform.revert +w name=afsearchCustomSearch');
      ProcessHelper::runOk('cv api4 Afform.revert +w name=afsearch_civix_exportmgd_CustomSearch');
      ProcessHelper::runOk("cv api4 SavedSearch.delete +w id=$ssId");
    }
  }

}
