<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\PhpData;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;
use Exception;

class AddEntityCommand extends \Symfony\Component\Console\Command\Command {
  const API_VERSION = 3;

  protected function configure() {
    $this
      ->setName('generate:entity')
      ->setDescription('Add a new API/BAO/GenCode entity to a CiviCRM Module-Extension (*EXPERIMENTAL AND INCOMPLETE*)')
      ->addArgument('<EntityName>', InputArgument::REQUIRED, 'The brief, unique name of the entity")');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    // load Civi to get access to civicrm_api_get_function_name
    Services::boot(['output' => $output]);
    $civicrm_api3 = Services::api3();
    if (!$civicrm_api3 || !$civicrm_api3->local) {
      $output->writeln("<error>Require access to local CiviCRM source tree. Configure civicrm_api3_conf_path.</error>");
      return;
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

    if (!preg_match('/^[A-Za-z0-9]+$/', $input->getArgument('<EntityName>'))) {
      throw new Exception("Entity name must be alphanumeric camel-case");
    }

    $ctx['entityNameCamel'] = ucfirst($input->getArgument('<EntityName>'));
    $ctx['tableName'] = 'civicrm_' . strtolower($input->getArgument('<EntityName>'));
    if (function_exists('civicrm_api_get_function_name')) {
      $ctx['apiFunctionPrefix'] = strtolower(civicrm_api_get_function_name($ctx['entityNameCamel'], '', self::API_VERSION));
    }
    elseif (function_exists('_civicrm_api_get_entity_name_from_camel')) {
      $ctx['apiFunctionPrefix'] = 'civicrm_api' . self::API_VERSION . '_' . _civicrm_api_get_entity_name_from_camel($ctx['entityNameCamel']) . '_' . $ctx['actionNameCamel'];
    }
    else {
      throw new Exception("Failed to determine proper API function name. Perhaps the API internals have changed?");
    }
    $ctx['apiFile'] = $basedir->string('api', 'v3', $ctx['entityNameCamel'] . '.php');
    $ctx['daoClassName'] = strtr($ctx['namespace'], '/', '_') . '_DAO_' . $input->getArgument('<EntityName>');
    $ctx['daoClassFile'] = $basedir->string(strtr($ctx['daoClassName'], '_', '/') . '.php');
    $ctx['baoClassName'] = strtr($ctx['namespace'], '/', '_') . '_BAO_' . $input->getArgument('<EntityName>');
    $ctx['baoClassFile'] = $basedir->string(strtr($ctx['baoClassName'], '_', '/') . '.php');
    $ctx['schemaFile'] = $basedir->string('xml', 'schema', $ctx['namespace'], $input->getArgument('<EntityName>') . '.xml');
    $ctx['entityTypeFile'] = $basedir->string('xml', 'schema', $ctx['namespace'], $input->getArgument('<EntityName>') . '.entityType.php');

    $ext = new Collection();
    $ext->builders['dirs'] = new Dirs([
      dirname($ctx['apiFile']),
      dirname($ctx['daoClassFile']),
      dirname($ctx['baoClassFile']),
      dirname($ctx['schemaFile']),
    ]);
    $ext->builders['dirs']->save($ctx, $output);

    $ext->builders['api.php'] = new Template('entity-api.php.php', $ctx['apiFile'], FALSE, Services::templating());
    $ext->builders['bao.php'] = new Template('entity-bao.php.php', $ctx['baoClassFile'], FALSE, Services::templating());
    $ext->builders['entity.xml'] = new Template('entity-schema.xml.php', $ctx['schemaFile'], FALSE, Services::templating());

    if (!file_exists($ctx['entityTypeFile'])) {
      $mgdEntities = [
        [
          'name' => $ctx['entityNameCamel'],
          'class' => $ctx['daoClassName'],
          'table' => $ctx['tableName'],
        ],
      ];
      $header = "// This file declares a new entity type. For more details, see \"hook_civicrm_entityTypes\" at:\n"
        . "// http://wiki.civicrm.org/confluence/display/CRMDOC/Hook+Reference";
      $ext->builders['entityType.php'] = new PhpData($ctx['entityTypeFile'], $header);
      $ext->builders['entityType.php']->set($mgdEntities);
    }

    $ext->init($ctx);
    $ext->save($ctx, $output);
  }

}
