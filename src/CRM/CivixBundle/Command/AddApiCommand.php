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
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;
use Exception;

class AddApiCommand extends ContainerAwareCommand
{
    const API_VERSION = 3;

    protected function configure()
    {
        $this
            ->setName('generate:api')
            ->setDescription('Add a new API function to a CiviCRM Module-Extension')
            ->addArgument('entityName', InputArgument::REQUIRED, 'The entity against which the action runs (eg "Contact", "MyEntity")')
            ->addArgument('actionName', InputArgument::REQUIRED, 'The action which will be created (eg "Create", "MyAction")')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // load Civi to get access to civicrm_api_get_function_name
        $civicrm_api3 = $this->getContainer()->get('civicrm_api3');
        if (!$civicrm_api3 || !$civicrm_api3->local) {
            $output->writeln("<error>--copy requires access to local CiviCRM source tree. Configure civicrm_api3_conf_path.</error>");
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
            $output->writeln('<error>Wrong extension type: '. $attrs['type'] . '</errror>');
            return;
        }

        if (!preg_match('/^[A-Za-z0-9]+$/', $input->getArgument('entityName'))) {
            throw new Exception("Entity name must be alphanumeric camel-case");
        }
        if (!preg_match('/^[A-Za-z0-9]+$/', $input->getArgument('actionName'))) {
            throw new Exception("Action name must be alphanumeric camel-case");
        }

        $ctx['entityNameCamel'] = ucfirst($input->getArgument('entityName'));
        $ctx['actionNameCamel'] = ucfirst($input->getArgument('actionName'));
        $ctx['apiFunction'] = strtolower(civicrm_api_get_function_name($ctx['entityNameCamel'], $ctx['actionNameCamel'], self::API_VERSION));
        $ctx['apiFile'] = $basedir->string('api', 'v3', $ctx['entityNameCamel'], $ctx['actionNameCamel'] . '.php');

        $dirs = new Dirs(array(
            dirname($ctx['apiFile'])
        ));
        $dirs->save($ctx, $output);

        if (!file_exists($ctx['apiFile'])) {
            $output->writeln(sprintf('<info>Write %s</info>', $ctx['apiFile']));
            file_put_contents($ctx['apiFile'], $this->getContainer()->get('templating')->render('CRMCivixBundle:Code:api.php.php', $ctx));
        } else {
            $output->writeln(sprintf('<error>Skip %s: file already exists</error>', $ctx['apiFile']));
        }

        $module = new Module($this->getContainer()->get('templating'));
        $module->loadInit($ctx);
        $module->save($ctx, $output);
    }
}
