<?php

namespace CRM\CivixBundle\Builder;

/**
 * @group unit
 */
class InfoTest extends \PHPUnit\Framework\TestCase {

  private $tempDir;

  public function setUp(): void {
    parent::setUp();
    $this->tempDir = sys_get_temp_dir() . '/civix_test_' . uniqid();
    mkdir($this->tempDir);
  }

  public function tearDown(): void {
    parent::tearDown();
    if (is_dir($this->tempDir)) {
      array_map('unlink', glob("$this->tempDir/*"));
      rmdir($this->tempDir);
    }
  }

  public function testInitPhpCompatibility(): void {
    $infoFile = $this->tempDir . '/info.xml';
    $builder = new Info($infoFile);
    $ctx = [
      'fullName' => 'org.example.testext',
      'type' => 'module',
      'mainFile' => 'testext',
      'compatibilityVerMin' => '5.60',
    ];
    $builder->init($ctx);

    $this->assertTrue($builder->hasPhpCompatibility());
    $this->assertEquals(Info::CURRENT_PHP_VERSIONS, $builder->getPhpCompatibilityVers());
    $this->assertEquals(Info::CURRENT_PHP_VERSIONS[0], $builder->getPhpCompatibilityVer('MIN'));
    $this->assertEquals(Info::CURRENT_PHP_VERSIONS[count(Info::CURRENT_PHP_VERSIONS) - 1], $builder->getPhpCompatibilityVer('MAX'));
  }

  public function testSetPhpCompatibilityWhenMissing(): void {
    $infoFile = $this->tempDir . '/info.xml';
    file_put_contents($infoFile, '<extension key="org.example.testext" type="module"></extension>');

    $builder = new Info($infoFile);
    $ctx = [];
    $builder->load($ctx);

    $this->assertFalse($builder->hasPhpCompatibility());
    $this->assertEmpty($builder->getPhpCompatibilityVers());
    $this->assertNull($builder->getPhpCompatibilityVer('MIN'));

    $builder->setPhpCompatibility(['8.1', '8.2', '8.3']);
    $this->assertTrue($builder->hasPhpCompatibility());
    $this->assertEquals(['8.1', '8.2', '8.3'], $builder->getPhpCompatibilityVers());
    $this->assertEquals('8.1', $builder->getPhpCompatibilityVer('MIN'));
    $this->assertEquals('8.3', $builder->getPhpCompatibilityVer('MAX'));

    $xml = $builder->get();
    $this->assertEquals('list', (string) $xml->php_compatibility['mode']);
  }

  public function testSetPhpCompatibilityWhenExisting(): void {
    $infoFile = $this->tempDir . '/info.xml';
    file_put_contents($infoFile, '<extension key="org.example.testext" type="module"><php_compatibility mode="list"><ver>8.0</ver><ver>8.1</ver></php_compatibility></extension>');

    $builder = new Info($infoFile);
    $ctx = [];
    $builder->load($ctx);

    $this->assertTrue($builder->hasPhpCompatibility());
    $this->assertEquals(['8.0', '8.1'], $builder->getPhpCompatibilityVers());

    $builder->setPhpCompatibility(['8.1', '8.2', '8.3', '8.4', '8.5']);
    $this->assertEquals(['8.1', '8.2', '8.3', '8.4', '8.5'], $builder->getPhpCompatibilityVers());
    $this->assertEquals('8.1', $builder->getPhpCompatibilityVer('MIN'));
    $this->assertEquals('8.5', $builder->getPhpCompatibilityVer('MAX'));
  }

}
