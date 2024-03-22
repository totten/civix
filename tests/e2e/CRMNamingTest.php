<?php

namespace E2E;

use CRM\CivixBundle\Builder\Info;

class CRMNamingTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_crmnaming';

  /**
   * @var \CRM\CivixBundle\Generator
   */
  protected $upgrader;

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
    $this->civixGenerateModule(static::getKey());
    chdir(static::getKey());

    $this->assertFileGlobs([
      'info.xml' => 1,
      'civix_crmnaming.php' => 1,
      'civix_crmnaming.civix.php' => 1,
    ]);

    \Civix::ioStack()->push(...$this->createInputOutput());
    $this->upgrader = \Civix::generator(static::getExtPath());
    $this->upgrader->updateInfo(function(Info $info) {
      // FIXME: Allow "_" instead of "/"
      $info->get()->civix->namespace = 'CRM/NamingTest';
    });
  }

  protected function tearDown(): void {
    parent::tearDown();
    \Civix::ioStack()->reset();
  }

  public function testNaming_OnePart(): void {
    $vars = $this->upgrader->createClassVars('CRM_NamingTest_Widget');
    $this->assertTrue(is_string($vars['extBaseDir']) && is_dir($vars['extBaseDir']));
    $this->assertEquals('civix_crmnaming', $vars['extMainFile']);
    $this->assertEquals('civix_crmnaming', $vars['extKey']);
    $this->assertEquals('CRM/NamingTest/Widget.php', $vars['classFile']);
    $this->assertEquals('CRM_NamingTest_Widget', $vars['className']);
    $this->assertEquals('CRM_NamingTest_Widget', $vars['classNameFull']);
    $this->assertEquals('', $vars['classNamespace']);
    $this->assertEquals('', $vars['classNamespaceDecl']);
    $this->assertEquals('use CRM_NamingTest_ExtensionUtil as E;', $vars['useE']);
  }

  public function testNaming_TwoParts(): void {
    $vars = $this->upgrader->createClassVars('CRM_NamingTest_Widget_Gizmo');
    $this->assertTrue(is_string($vars['extBaseDir']) && is_dir($vars['extBaseDir']));
    $this->assertEquals('civix_crmnaming', $vars['extMainFile']);
    $this->assertEquals('civix_crmnaming', $vars['extKey']);
    $this->assertEquals('CRM/NamingTest/Widget/Gizmo.php', $vars['classFile']);
    $this->assertEquals('CRM_NamingTest_Widget_Gizmo', $vars['className']);
    $this->assertEquals('CRM_NamingTest_Widget_Gizmo', $vars['classNameFull']);
    $this->assertEquals('', $vars['classNamespace']);
    $this->assertEquals('', $vars['classNamespaceDecl']);
    $this->assertEquals('use CRM_NamingTest_ExtensionUtil as E;', $vars['useE']);
  }

}
