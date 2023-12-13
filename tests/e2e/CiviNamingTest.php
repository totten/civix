<?php

namespace E2E;

use CRM\CivixBundle\Builder\Info;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;

class CiviNamingTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_civinaming';

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
      'civix_civinaming.php' => 1,
      'civix_civinaming.civix.php' => 1,
    ]);

    \Civix::ioStack()->push(new ArgvInput(), new NullOutput());
    $this->upgrader = \Civix::generator(static::getExtPath());
    $this->upgrader->updateInfo(function(Info $info) {
      // FIXME: Allow "\" instead of "/"
      $info->get()->civix->namespace = 'Civi/NamingTest';
    });
  }

  protected function tearDown(): void {
    parent::tearDown();
    \Civix::ioStack()->reset();
  }

  public function testNaming_OnePart(): void {
    $vars = $this->upgrader->createClassVars('Civi\\NamingTest\\Widget');
    $this->assertTrue(is_string($vars['extBaseDir']) && is_dir($vars['extBaseDir']));
    $this->assertEquals('civix_civinaming', $vars['extMainFile']);
    $this->assertEquals('civix_civinaming', $vars['extKey']);
    $this->assertEquals('Civi/NamingTest/Widget.php', $vars['classFile']);
    $this->assertEquals('Widget', $vars['className']);
    $this->assertEquals('Civi\\NamingTest\\Widget', $vars['classNameFull']);
    $this->assertEquals('Civi\\NamingTest', $vars['classNamespace']);
    $this->assertEquals('namespace Civi\\NamingTest;', $vars['classNamespaceDecl']);
    $this->assertEquals('use CRM_NamingTest_ExtensionUtil as E;', $vars['useE']);
  }

  public function testNaming_TwoParts(): void {
    $vars = $this->upgrader->createClassVars('Civi\\NamingTest\\Widget\\Gizmo');
    $this->assertTrue(is_string($vars['extBaseDir']) && is_dir($vars['extBaseDir']));
    $this->assertEquals('civix_civinaming', $vars['extMainFile']);
    $this->assertEquals('civix_civinaming', $vars['extKey']);
    $this->assertEquals('Civi/NamingTest/Widget/Gizmo.php', $vars['classFile']);
    $this->assertEquals('Gizmo', $vars['className']);
    $this->assertEquals('Civi\\NamingTest\\Widget\\Gizmo', $vars['classNameFull']);
    $this->assertEquals('Civi\\NamingTest\\Widget', $vars['classNamespace']);
    $this->assertEquals('namespace Civi\\NamingTest\\Widget;', $vars['classNamespaceDecl']);
    $this->assertEquals('use CRM_NamingTest_ExtensionUtil as E;', $vars['useE']);
  }

}
