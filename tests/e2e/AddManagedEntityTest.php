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
    $this->assertMixinStatuses(['mgd-php@1' => 'off']);
    $this->assertFileGlobs(['managed/OptionGroup_preferred_communication_method.mgd.php' => 0]);

    $tester = static::civix('generate:managed');
    $tester->execute(['<EntityName>' => 'OptionGroup', '<EntityId>' => 1]);
    if ($tester->getStatusCode() !== 0) {
      throw new \RuntimeException(sprintf("Failed to generate mgd (%s)", static::getKey()));
    }

    $this->assertMixinStatuses(['mgd-php@1' => 'on']);
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

}
