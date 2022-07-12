<?php

namespace E2E;

class AddEntityTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_addentity';

  private $entityFiles = [
    'CRM/CivixAddentity/DAO/Bread.php',
    'CRM/CivixAddentity/BAO/Bread.php',
    'xml/schema/CRM/CivixAddentity/Bread.xml',
    'xml/schema/CRM/CivixAddentity/Bread.entityType.php',

    'CRM/CivixAddentity/DAO/Sandwich.php',
    'CRM/CivixAddentity/BAO/Sandwich.php',
    'xml/schema/CRM/CivixAddentity/Sandwich.xml',
    'xml/schema/CRM/CivixAddentity/Sandwich.entityType.php',

    'CRM/CivixAddentity/DAO/Flour.php',
    'CRM/CivixAddentity/BAO/Flour.php',
    'xml/schema/CRM/CivixAddentity/Flour.xml',
    'xml/schema/CRM/CivixAddentity/Flour.entityType.php',
  ];

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
    $this->civixGenerateModule(static::getKey());
    chdir(static::getKey());

    $this->assertFileGlobs([
      'info.xml' => 1,
      'civix_addentity.php' => 1,
      'civix_addentity.civix.php' => 1,
    ]);
  }

  public function testAddEntity(): void {
    $this->assertFileGlobs([
      'sql/auto_install.sql' => 0,
    ]);
    $this->assertFileGlobs(array_fill_keys($this->entityFiles, 0));

    $this->civixGenerateEntity('Bread');
    $this->civixGenerateEntity('Sandwich');
    $this->civixGenerateEntity('Meal');
    $this->civixGenerateEntity('Flour');
    $this->civixGenerateEntityBoilerplate();
    $this->addExampleFK($this->getExtPath('xml/schema/CRM/CivixAddentity/Bread.xml'), 'flour', 'civicrm_flour');
    $this->addExampleFK($this->getExtPath('xml/schema/CRM/CivixAddentity/Sandwich.xml'), 'bread', 'civicrm_bread');
    $this->addExampleFK($this->getExtPath('xml/schema/CRM/CivixAddentity/Meal.xml'), 'sandwich', 'civicrm_sandwich');
    $this->civixGenerateEntityBoilerplate();

    $this->assertFileGlobs([
      'sql/auto_install.sql' => 1,
    ]);
    $this->assertFileGlobs(array_fill_keys($this->entityFiles, 1));

    $install = $this->grepLines(';CREATE TABLE;', $this->getExtPath('sql/auto_install.sql'));
    $uninstall = $this->grepLines(';DROP TABLE;', $this->getExtPath('sql/auto_uninstall.sql'));

    $this->assertEquals([
      'CREATE TABLE `civicrm_flour` (',
      'CREATE TABLE `civicrm_bread` (',
      'CREATE TABLE `civicrm_sandwich` (',
      'CREATE TABLE `civicrm_meal` (',
    ], $install);

    $this->assertEquals([
      'DROP TABLE IF EXISTS `civicrm_meal`;',
      'DROP TABLE IF EXISTS `civicrm_sandwich`;',
      'DROP TABLE IF EXISTS `civicrm_bread`;',
      'DROP TABLE IF EXISTS `civicrm_flour`;',
    ], $uninstall);
  }

  private function grepLines(string $pattern, string $file): array {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    return array_values(preg_grep($pattern, $lines));
  }

  private function addExampleFK(string $xmlFile, string $field, string $foreignTable) {
    $newXmlTpl = '<field>
      <name>%%FIELD%%</name>
      <title>%%FIELD%% ID</title>
      <type>int unsigned</type>
      <comment>FK to %%TABLE%% ID</comment>
      <html>
        <label>%%TABLE%%</label>
      </html>
      <add>2.0</add>
    </field>
    <foreignKey>
      <name>%%FIELD%%</name>
      <table>%%TABLE%%</table>
      <key>id</key>
      <add>2.0</add>
      <onDelete>CASCADE</onDelete>
    </foreignKey>';
    $newXml = strtr($newXmlTpl, [
      '%%FIELD%%' => $field,
      '%%TABLE%%' => $foreignTable,
    ]);

    $tail = '</table>';

    $raw = file_get_contents($xmlFile);
    $raw = str_replace($tail, "{$newXml}\n{$tail}", $raw);
    file_put_contents($xmlFile, $raw);
  }

}
