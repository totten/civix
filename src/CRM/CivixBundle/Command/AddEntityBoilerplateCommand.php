<?php
namespace CRM\CivixBundle\Command;

use Civix;
use CRM\CivixBundle\Utils\Files;
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
        "Creates DAOs based on XML files.\n" .
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
    Civix::boot(['output' => $output]);
    $civicrm_api3 = Civix::api3();

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
    $this->resolveForeignKeys($tables);
    $config->tables = $tables;

    foreach ($tables as $table) {
      $dao = new \CRM_Core_CodeGen_DAO($config, (string) $table['name'], "{$_namespace}_ExtensionUtil::ts");
      // Don't display gencode's output
      ob_start();
      $dao->run();
      ob_end_clean();
      $daoFileName = $basedir->string("{$table['base']}{$table['fileName']}");
      $output->writeln("<info>Write</info>" . Files::relativize($daoFileName));
    }

    $module = new Module(Civix::templating());
    $module->loadInit($ctx);
    $module->save($ctx, $output);
    $upgraderClass = str_replace('/', '_', $ctx['namespace']) . '_Upgrader';

    if (!class_exists($upgraderClass)) {
      $output->writeln('<comment>You are missing an upgrader class. You will not be able to add install/upgrade steps. Fix this by running `civix generate:upgrader`.</comment>');
    }

    return 0;
  }

  private function resolveForeignKeys(&$tables) {
    foreach ($tables as &$table) {
      if (isset($table['foreignKey'])) {
        foreach ($table['foreignKey'] as &$key) {
          $key['className'] = $tables[$key['table']]['className'] ?? \CRM_Core_DAO_AllCoreTables::getClassForTable($key['table']);
          $table['fields'][$key['name']]['FKClassName'] = $key['className'];
          $table['fields'][$key['name']]['FKColumnName'] = $key['key'];
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
