<?php

namespace E2E;

class AddEntityTest extends \PHPUnit\Framework\TestCase {

  use CivixProjectTestTrait;

  public static $key = 'civix_addentity';

  private $entityFiles = [
    'CRM/CivixAddentity/DAO/Bread.php',
    'CRM/CivixAddentity/BAO/Bread.php',
    'schema/Bread.entityType.php',

    'CRM/CivixAddentity/DAO/Sandwich.php',
    'CRM/CivixAddentity/BAO/Sandwich.php',
    'schema/Sandwich.entityType.php',

    'CRM/CivixAddentity/DAO/Flour.php',
    'CRM/CivixAddentity/BAO/Flour.php',
    'schema/Flour.entityType.php',
  ];

  public function setUp(): void {
    chdir(static::getWorkspacePath());
    static::cleanDir(static::getKey());
    $this->civixGenerateModule(static::getKey(), [
      '--compatibility' => '5.69',
    ]);
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

    $this->civixGenerateUpgrader();
    $this->civixGenerateEntity('Bread');
    $this->civixGenerateEntity('Sandwich');
    $this->civixGenerateEntity('Meal');
    $this->civixGenerateEntity('Flour');
    $this->addExampleFK($this->getExtPath('xml/schema/CRM/CivixAddentity/Bread.xml'), 'flour', 'civicrm_flour');
    $this->addExampleFK($this->getExtPath('xml/schema/CRM/CivixAddentity/Sandwich.xml'), 'bread', 'civicrm_bread');
    $this->addExampleFK($this->getExtPath('xml/schema/CRM/CivixAddentity/Meal.xml'), 'sandwich', 'civicrm_sandwich');
    // add FK referencing its own table
    $this->addExampleFK($this->getExtPath('xml/schema/CRM/CivixAddentity/Meal.xml'), 'next_meal', 'civicrm_meal');

    $this->assertFileGlobs([
      // No longer use static files
      'sql/auto_install.sql' => 0,
    ]);
    $this->assertFileGlobs(array_fill_keys($this->entityFiles, 1));

    // FIXME: Perhaps call `cv ev 'E::schema()->generateInstallSql()` and restore the old assertions
    $this->assertEquals('CiviMix\\Schema\\CivixAddentity\\AutomaticUpgrader', trim($this->civixInfoGet('upgrader')->getDisplay()));
    $civixPhpFile = $this->getExtPath('civix_addentity.civix.php');
    $content = file_get_contents($civixPhpFile);
    $this->assertStringSequence([
      '($GLOBALS[\'_PathLoad\'][0] ?? require __DIR__ . \'/mixin/lib/pathload-0.php\');',
      'pathload()->addSearchDir(__DIR__ . \'/mixin/lib\');',
      '    pathload()->loadPackage(\'civimix-schema@5\', TRUE);',
    ], $content);
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
