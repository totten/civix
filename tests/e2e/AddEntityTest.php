<?php

namespace E2E;

use PhpArrayDocument\ArrayNode;
use PhpArrayDocument\PhpArrayDocument;
use PhpArrayDocument\ScalarNode;

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

    \Civix::ioStack()->push(...$this->createInputOutput());
    $this->assertFileGlobs([
      'info.xml' => 1,
      'civix_addentity.php' => 1,
      'civix_addentity.civix.php' => 1,
    ]);
  }

  protected function tearDown(): void {
    parent::tearDown();
    \Civix::ioStack()->reset();
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
    $this->addExampleFK($this->getExtPath('schema/Bread.entityType.php'), 'flour', 'Flour', 'civicrm_flour');
    $this->addExampleFK($this->getExtPath('schema/Sandwich.entityType.php'), 'bread', 'Bread', 'civicrm_bread');
    $this->addExampleFK($this->getExtPath('schema/Meal.entityType.php'), 'sandwich', 'Sandwich', 'civicrm_sandwich');
    // add FK referencing its own table
    $this->addExampleFK($this->getExtPath('schema/Meal.entityType.php'), 'next_meal', 'Meal', 'civicrm_meal');

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

  private function addExampleFK(string $schemaFile, string $fieldName, string $foreignEntity, string $foreignTable) {
    \Civix::generator()->updatePhpArrayDocument($schemaFile, function (PhpArrayDocument $doc) use ($fieldName, $foreignEntity, $foreignTable) {
      $field = ArrayNode::create()->importData([
        'title' => ScalarNode::create("$fieldName ID")->setFactory('E::ts'),
        'sql_type' => 'int unsigned',
        'input_type' => 'EntityRef',
        'description' => ScalarNode::create("FK to $foreignTable ID")->setFactory('E::ts'),
        'add' => '2.0',
        'input_attrs' => [
          'label' => ScalarNode::create("$foreignTable")->setFactory('E::ts'), /* weird */
        ],
        'entity_reference' => [
          'entity' => $foreignEntity,
          'key' => 'id',
          'on_delete' => 'CASCADE',
        ],
      ]);
      $doc->getRoot()['getFields'][$fieldName] = $field;
    });
  }

}
