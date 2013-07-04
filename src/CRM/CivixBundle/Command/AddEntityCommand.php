<?php
namespace CRM\CivixBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Menu;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Builder\PhpData;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;
use Exception;

class AddEntityCommand extends ContainerAwareCommand
{
    const API_VERSION = 3;

    protected function configure()
    {
        $this
            ->setName('generate:entity')
            ->setDescription('Add a new API/BAO/GenCode entity to a CiviCRM Module-Extension (*EXPERIMENTAL AND INCOMPLETE*)')
            ->addArgument('<EntityName>', InputArgument::REQUIRED, 'The brief, unique name of the entity")')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // load Civi to get access to civicrm_api_get_function_name
        $civicrm_api3 = $this->getContainer()->get('civicrm_api3');
        if (!$civicrm_api3 || !$civicrm_api3->local) {
            $output->writeln("<error>Require access to local CiviCRM source tree. Configure civicrm_api3_conf_path.</error>");
            return;
        }

        $ctx = array();
        $ctx['type'] = 'module';
        $ctx['basedir'] = rtrim(getcwd(),'/');
        $basedir = new Path($ctx['basedir']);

        $info = new Info($basedir->string('info.xml'));
        $info->load($ctx);
        $attrs = $info->get()->attributes();
        if ($attrs['type'] != 'module') {
            $output->writeln('<error>Wrong extension type: '. $attrs['type'] . '</error>');
            return;
        }

        if (!preg_match('/^[A-Za-z0-9]+$/', $input->getArgument('<EntityName>'))) {
            throw new Exception("Entity name must be alphanumeric camel-case");
        }

        $ctx['entityNameCamel'] = ucfirst($input->getArgument('<EntityName>'));
        $ctx['tableName'] = 'civicrm_' . strtolower($input->getArgument('<EntityName>'));
        $ctx['apiFunctionPrefix'] = strtolower(civicrm_api_get_function_name($ctx['entityNameCamel'], ''/*$ctx['actionNameCamel']*/, self::API_VERSION));
        $ctx['apiFile'] = $basedir->string('api', 'v3', $ctx['entityNameCamel']. '.php');
        $ctx['daoClassName'] = strtr($ctx['namespace'], '/', '_') . '_DAO_' . $input->getArgument('<EntityName>');
        $ctx['daoClassFile'] = $basedir->string(strtr($ctx['daoClassName'], '_', '/') . '.php');
        $ctx['baoClassName'] = strtr($ctx['namespace'], '/', '_') . '_BAO_' . $input->getArgument('<EntityName>');
        $ctx['baoClassFile'] = $basedir->string(strtr($ctx['baoClassName'], '_', '/') . '.php');
        $ctx['schemaFile'] = $basedir->string('xml', 'schema', $ctx['namespace'], $input->getArgument('<EntityName>') . '.xml');
        $ctx['entityTypeFile'] = $basedir->string('xml', 'schema', $ctx['namespace'], $input->getArgument('<EntityName>') . '.entityType.php');

        $ext = new Collection();
        $ext->builders['dirs'] = new Dirs(array(
            dirname($ctx['apiFile']),
            dirname($ctx['daoClassFile']),
            dirname($ctx['baoClassFile']),
            dirname($ctx['schemaFile']),
        ));
        $ext->builders['dirs']->save($ctx, $output);

        $ext->builders['api.php'] = new Template('CRMCivixBundle:Code:entity-api.php.php', $ctx['apiFile'], FALSE, $this->getContainer()->get('templating'));
        $ext->builders['bao.php'] = new Template('CRMCivixBundle:Code:entity-bao.php.php', $ctx['baoClassFile'], FALSE, $this->getContainer()->get('templating'));
        $ext->builders['entity.xml'] = new Template('CRMCivixBundle:Code:entity-schema.xml.php', $ctx['schemaFile'], FALSE, $this->getContainer()->get('templating'));

        if (!file_exists($ctx['entityTypeFile'])) {
            $mgdEntities = array(
              array(
                'name' => $ctx['entityNameCamel'],
                'class' => $ctx['daoClassName'],
                'table' => $ctx['tableName'],
              ),
            );
            $header = "// This file declares a new entity type. For more details, see \"hook_civicrm_entityTypes\" at:\n"
                . "// http://wiki.civicrm.org/confluence/display/CRMDOC/Hook+Reference";
            $ext->builders['entityType.php'] = new PhpData($ctx['entityTypeFile'], $header);
            $ext->builders['entityType.php']->set($mgdEntities);
        }

        $ext->init($ctx);
        $ext->save($ctx, $output);
    }
}
