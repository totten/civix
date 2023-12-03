<?php

namespace E2E;

use CRM\CivixBundle\Upgrader;
use CRM\CivixBundle\Utils\Path;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;

class CRMNamingTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_crmnaming';

  /**
   * @var \CRM\CivixBundle\Upgrader
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

    $this->upgrader = new Upgrader(new ArgvInput(), new NullOutput(), new Path(static::getExtPath()));
  }

  public function testNaming_OneParts(): void {
    $vars = $this->upgrader->createClassVars('Widget');
    $this->assertTrue(is_string($vars['extBaseDir']) && is_dir($vars['extBaseDir']));
    $this->assertEquals('civix_crmnaming', $vars['extMainFile']);
    $this->assertEquals('civix_crmnaming', $vars['extKey']);
    $this->assertEquals('CRM/CivixCrmnaming/Widget.php', $vars['classFile']);
    $this->assertEquals('CRM_CivixCrmnaming_Widget', $vars['className']);
    $this->assertEquals('CRM_CivixCrmnaming_Widget', $vars['classNameFull']);
    $this->assertEquals('', $vars['classNamespace']);
    $this->assertEquals('', $vars['classNamespaceDecl']);
    $this->assertEquals('use CRM_CivixCrmnaming_ExtensionUtil as E;', $vars['useE']);
  }

  public function testNaming_TwoParts(): void {
    $vars = $this->upgrader->createClassVars(['Widget', 'Gizmo']);
    $this->assertTrue(is_string($vars['extBaseDir']) && is_dir($vars['extBaseDir']));
    $this->assertEquals('civix_crmnaming', $vars['extMainFile']);
    $this->assertEquals('civix_crmnaming', $vars['extKey']);
    $this->assertEquals('CRM/CivixCrmnaming/Widget/Gizmo.php', $vars['classFile']);
    $this->assertEquals('CRM_CivixCrmnaming_Widget_Gizmo', $vars['className']);
    $this->assertEquals('CRM_CivixCrmnaming_Widget_Gizmo', $vars['classNameFull']);
    $this->assertEquals('', $vars['classNamespace']);
    $this->assertEquals('', $vars['classNamespaceDecl']);
    $this->assertEquals('use CRM_CivixCrmnaming_ExtensionUtil as E;', $vars['useE']);
  }

}