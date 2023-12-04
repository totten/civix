<?php

namespace E2E;

class AddServiceTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_addsvc';

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
    $this->civixGenerateModule(static::getKey());
    chdir(static::getKey());

    $this->assertFileGlobs([
      'info.xml' => 1,
      'civix_addsvc.php' => 1,
      'civix_addsvc.civix.php' => 1,
    ]);
  }

  public function testAddService(): void {
    $this->assertFileGlobs([
      'CRM/CivixAddsvc/Whiz/Bang.php' => 0,
    ]);

    $this->civixGenerateService('civix_addsvc.whiz.bang');

    $this->assertFileGlobs([
      'CRM/CivixAddsvc/Whiz/Bang.php' => 1,
    ]);

    $code = file_get_contents('CRM/CivixAddsvc/Whiz/Bang.php');
    $expect = [
      'use CRM_CivixAddsvc_ExtensionUtil as E',
      'use Civi\Core\Service\AutoService',
      'use Symfony\Component\EventDispatcher\EventSubscriberInterface',
      '@service civix_addsvc.whiz.bang',
      'class CRM_CivixAddsvc_Whiz_Bang extends AutoService implements EventSubscriberInterface',
      'function getSubscribedEvents',
    ];
    $this->assertStringSequence($expect, $code);
  }

}
