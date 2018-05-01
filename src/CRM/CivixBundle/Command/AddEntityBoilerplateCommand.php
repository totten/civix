<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Utils\Path;
use Exception;

class AddEntityBoilerplateCommand extends \Symfony\Component\Console\Command\Command {
  const API_VERSION = 3;

  protected function configure() {
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

  protected function execute(InputInterface $input, OutputInterface $output) {
    // load Civi to get access to civicrm_api_get_function_name
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
    // var_dump(($absXmlSchemaGlob));
    $xmlSchemas = glob($absXmlSchemaGlob);

    if(!count($xmlSchemas)){
      throw new Exception("Could not find files matching '$xmlSchemaGlob'. You may want to run `civix generate:entity` before running this command.");
    }

    $specification = new \CRM_Core_CodeGen_Specification;
    $specification->buildVersion = \CRM_Utils_System::majorVersion();

    $config = new \stdClass;
    $config->phpCodePath = $basedir->string('');
    $config->sqlCodePath = $basedir->string('sql/');

    foreach($xmlSchemas as $xmlSchema){

      $dom = new \DomDocument();
      $xmlString = file_get_contents($xmlSchema);
      $dom->loadXML($xmlString);
      $xml = simplexml_import_dom($dom);

      $specification->getTable($xml, $database, $tables);

      $tables[(string) $xml->name]['sourceFile'] = $xmlSchema;
      $config->tables = $tables;

      $dao = new \CRM_Core_CodeGen_DAO($config, (string) $xml->name);
      ob_start(); // Don't display gencode's output
      $dao->run();
      ob_end_clean(); // Don't display gencode's output
      $daoFileName = $basedir->string("{$xml->base}/DAO/{$xml->class}.php");
      $output->writeln("<info>Write $daoFileName</info>");

    }

    $schema = new \CRM_Core_CodeGen_Schema($config);
    \CRM_Core_CodeGen_Util_File::createDir($config->sqlCodePath);
    ob_start(); // Don't display gencode's output
    $schema->generateCreateSql('auto_install.sql');
    ob_end_clean(); // Don't display gencode's output
    $output->writeln("<info>Write {$basedir->string('sql/auto_install.sql')}</info>");
    ob_start(); // Don't display gencode's output
    $schema->generateDropSql('auto_uninstall.sql');
    $output->writeln("<info>Write {$basedir->string('sql/auto_uninstall.sql')}</info>");
    ob_end_clean(); // Don't display gencode's output

    $module = new Module(Services::templating());
    $module->loadInit($ctx);
    $module->save($ctx, $output);

    $upgraderClass = str_replace('/', '_', $ctx['namespace']).'_Upgrader';
    if(!class_exists($upgraderClass)){
      $output->writeln('<comment>You are missing an upgrader class. Your generated SQL files will not be executed on enable and uninstall. Fix this by running `civix generate:upgrader`.</comment>');
    }

  }

}
