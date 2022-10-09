<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Utils\Path;
use Exception;

class AddEntityBoilerplateCommand extends AbstractCommand {
  const API_VERSION = 3;

  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:entity-boilerplate')
      ->setDescription('Generates boilerplate code for entities based on xml schema definition files (*EXPERIMENTAL AND INCOMPLETE*)')
      ->setHelp(
        "Creates DAOs, mysql install and uninstall instructions, and an appropriate\n" .
        "hook_civicrm_entityTypes based on this extension's xml schema files.\n" .
        "\n" .
        "Typically you will run this command after creating or updating one or more\n" .
        "xml/schema/CRM/NameSpace/EntityName.xml files.\n"
      );
  }

  /**
   * Note: this function replicates a fair amount of the functionality of
   * CRM_Core_CodeGen_Specification (which is a bit messy and hard to interact
   * with). It's tempting to completely rewrite / rethink entity generation. Until
   * then...
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    Services::boot(['output' => $output]);
    $civicrm_api3 = Services::api3();

    if (!$civicrm_api3 || !$civicrm_api3->local) {
      $output->writeln("<error>Require access to local CiviCRM source tree. Configure civicrm_api3_conf_path.</error>");
      return 1;
    }
    if (version_compare(\CRM_Utils_System::version(), '4.7.0', '<=')) {
      $output->writeln("<error>This command requires CiviCRM 4.7+.</error>");
      return 1;
    }

    $this->assertCurrentFormat();

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);
    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);
    $attrs = $info->get()->attributes();

    if ($attrs['type'] != 'module') {
      $output->writeln('<error>Wrong extension type: ' . $attrs['type'] . '</error>');
      return;
    }

    $xmlSchemaGlob = "xml/schema/{$ctx['namespace']}/*.xml";
    $absXmlSchemaGlob = $basedir->string($xmlSchemaGlob);
    $xmlSchemas = glob($absXmlSchemaGlob);

    if (!count($xmlSchemas)) {
      throw new Exception("Could not find files matching '$xmlSchemaGlob'. You may want to run `civix generate:entity` before running this command.");
    }

    $specification = new \CRM_Core_CodeGen_Specification();
    $specification->buildVersion = \CRM_Utils_System::majorVersion();
    $config = new \stdClass();
    $config->phpCodePath = $basedir->string('');
    $config->sqlCodePath = $basedir->string('sql/');
    $config->database = $this->getDefaultDatabase();

    foreach ($xmlSchemas as $xmlSchema) {
      $dom = new \DomDocument();
      $xmlString = file_get_contents($xmlSchema);
      $dom->loadXML($xmlString);
      $xml = simplexml_import_dom($dom);
      if (!$xml) {
        $output->writeln('<error>There is an error in the XML for ' . $xmlSchema . '</error>');
        continue;
      }
      /** @var array $tables */
      $specification->getTable($xml, $config->database, $tables);
      $name = (string) $xml->name;
      $tables[$name]['name'] = $name;
      $sourcePath = strstr($xmlSchema, "/xml/schema/{$ctx['namespace']}/");
      $tables[$name]['sourceFile'] = $ctx['fullName'] . $sourcePath;
    }

    $config->tables = $tables;
    $_namespace = ' ' . preg_replace(':/:', '_', $ctx['namespace']);
    $this->orderTables($tables, $output);
    $this->resolveForeignKeys($tables);
    $config->tables = $tables;

    foreach ($tables as $table) {
      $dao = new \CRM_Core_CodeGen_DAO($config, (string) $table['name'], "{$_namespace}_ExtensionUtil::ts");
      // Don't display gencode's output
      ob_start();
      $dao->run();
      ob_end_clean();
      $daoFileName = $basedir->string("{$table['base']}{$table['fileName']}");
      $output->writeln("<info>Write</info> $daoFileName");
    }

    $schema = new \CRM_Core_CodeGen_Schema($config);
    \CRM_Core_CodeGen_Util_File::createDir($config->sqlCodePath);

    /**
     * @param string $generator
     *   The desired $schema->$generator() function which will produce the file.
     * @param string $fileName
     *   The desired basename of the SQL file.
     */
    $createSql = function($generator, $fileName) use ($output, $schema, $config) {
      $filePath = $config->sqlCodePath . $fileName;
      // We're poking into an internal class+function (`$schema->$generator()`) that changed in v5.23.
      // Beginning in 5.23: $schema->$function() returns an array with file content.
      // Before 5.23: $schema->$function($fileName) creates $fileName and returns void.
      $output->writeln("<info>Write</info> $filePath");
      if (version_compare(\CRM_Utils_System::version(), '5.23.alpha1', '>=')) {
        $data = $schema->$generator();
        if (!file_put_contents($filePath, reset($data))) {
          $output->writeln("<error>Failed to write data to {$filePath}</error>");
        }
      }
      else {
        $output->writeln("<error>WARNING</error>: Support for generating entities on <5.23 is deprecated.");
        // Don't display gencode's output
        ob_start();
        $schema->$generator($fileName);
        ob_end_clean();
      }
    };
    $createSql('generateCreateSql', 'auto_install.sql');
    $createSql('generateDropSql', 'auto_uninstall.sql');

    $module = new Module(Services::templating());
    $module->loadInit($ctx);
    $module->save($ctx, $output);
    $upgraderClass = str_replace('/', '_', $ctx['namespace']) . '_Upgrader';

    if (!class_exists($upgraderClass)) {
      $output->writeln('<comment>You are missing an upgrader class. Your generated SQL files will not be executed on enable and uninstall. Fix this by running `civix generate:upgrader`.</comment>');
    }

  }

  private function orderTables(&$tables, $output) {

    $ordered = [];
    $abort = count($tables);

    while (count($tables)) {
      // Safety valve
      if ($abort-- == 0) {
        $output->writeln("<error>Cannot determine FK ordering of tables.</error>  Do you have circular Foreign Keys?  Change your FK's or fix your auto_install.sql");
        break;
      }
      // Consider each table
      foreach ($tables as $k => $table) {
        // No FK's? Easy - add now
        if (!isset($table['foreignKey'])) {
          $ordered[$k] = $table;
          unset($tables[$k]);
        }
        if (isset($table['foreignKey'])) {
          // If any FK references a table still in our list (but is not a self-reference),
          // skip this table for now
          foreach ($table['foreignKey'] as $fKey) {
            if (in_array($fKey['table'], array_keys($tables)) && $fKey['table'] != $table['name']) {
              continue 2;
            }
          }
          // If we get here, all FK's reference already added tables or external tables so add now
          $ordered[$k] = $table;
          unset($tables[$k]);
        }
      }
    }
    $tables = $ordered;
  }

  private function resolveForeignKeys(&$tables) {
    foreach ($tables as &$table) {
      if (isset($table['foreignKey'])) {
        foreach ($table['foreignKey'] as &$key) {
          if (isset($tables[$key['table']])) {
            $key['className'] = $tables[$key['table']]['className'];
            $key['fileName'] = $tables[$key['table']]['fileName'];
            $table['fields'][$key['name']]['FKClassName'] = $key['className'];
          }
          else {
            $key['className'] = \CRM_Core_DAO_AllCoreTables::getClassForTable($key['table']);
            $key['fileName'] = $key['className'] . '.php';
            $table['fields'][$key['name']]['FKClassName'] = $key['className'];
          }
        }
      }
    }
  }

  /**
   * Get general/default database options (eg character set, collation).
   *
   * In civicrm-core, the `database` definition comes from
   * `xml/schema/Schema.xml` and `$spec->getDatabase($dbXml)`.
   *
   * Civix uses different defaults. Explanations are inlined below.
   *
   * @return array
   */
  private function getDefaultDatabase(): array {
    return [
      'name' => '',
      'attributes' => '',
      'tableAttributes_modern' => 'ENGINE=InnoDB',
      'tableAttributes_simple' => 'ENGINE=InnoDB',
      // ^^ Set very limited defaults.
      // Existing deployments may be inconsistent with respect to charsets and collations, and
      // it's hard to attune with static code. This represents a compromise (until we can
      // rework the process in a way that clearly addresses the inconsistencies among deployments).
      'comment' => '',
    ];
  }

}
