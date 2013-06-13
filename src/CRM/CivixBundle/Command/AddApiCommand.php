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

class AddApiCommand extends ContainerAwareCommand
{
    const API_VERSION = 3;

    public static function getSchedules() {
        return array('Daily','Hourly','Always');
    }

    protected function configure()
    {
        $this
            ->setName('generate:api')
            ->setDescription('Add a new API function to a CiviCRM Module-Extension')
            ->addArgument('<EntityName>', InputArgument::REQUIRED, 'The entity against which the action runs (eg "Contact", "MyEntity")')
            ->addArgument('<ActionName>', InputArgument::REQUIRED, 'The action which will be created (eg "Create", "MyAction")')
            ->addOption('schedule', null, InputOption::VALUE_OPTIONAL, 'Schedule this action as a recurring cron job ('.implode(', ', self::getSchedules()).') [For CiviCRM 4.3+]')
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
            $output->writeln('<error>Wrong extension type: '. $attrs['type'] . '</error>');
            return;
        }

        if (!preg_match('/^[A-Za-z0-9]+$/', $input->getArgument('<EntityName>'))) {
            throw new Exception("Entity name must be alphanumeric camel-case");
        }
        if (!preg_match('/^[A-Za-z0-9]+$/', $input->getArgument('<ActionName>'))) {
            throw new Exception("Action name must be alphanumeric camel-case");
        }
        if ($input->getOption('schedule') && !in_array($input->getOption('schedule'), self::getSchedules())) {
            throw new Exception("Schedule must be one of: " . implode(', ', self::getSchedules()));
        }

        $ctx['entityNameCamel'] = ucfirst($input->getArgument('<EntityName>'));
        $ctx['actionNameCamel'] = ucfirst($input->getArgument('<ActionName>'));
        $ctx['apiFunction'] = strtolower(civicrm_api_get_function_name($ctx['entityNameCamel'], $ctx['actionNameCamel'], self::API_VERSION));
        $ctx['apiFile'] = $basedir->string('api', 'v3', $ctx['entityNameCamel'], $ctx['actionNameCamel'] . '.php');
        $ctx['apiCronFile'] = $basedir->string('api', 'v3', $ctx['entityNameCamel'], $ctx['actionNameCamel'] . '.mgd.php');

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

        if ($input->getOption('schedule')) {
            if (!file_exists($ctx['apiCronFile'])) {
                $mgdEntities = array(
                  array(
                    'name' => 'Cron:' . $ctx['entityNameCamel'] . '.' . $ctx['actionNameCamel'],
                    'entity' => 'Job',
                    'params' => array(
                      'version' => 3,
                      'name' => sprintf('Call %s.%s API', $ctx['entityNameCamel'], $ctx['actionNameCamel']),
                      'description' => sprintf('Call %s.%s API', $ctx['entityNameCamel'], $ctx['actionNameCamel']),
                      'run_frequency' => $input->getOption('schedule'),
                      'api_entity' => $ctx['entityNameCamel'],
                      'api_action' => $ctx['actionNameCamel'],
                      'parameters' => '',
                    ),
                  ),
                );
                $header = "// This file declares a managed database record of type \"Job\".\n"
                    . "// The record will be automatically inserted, updated, or deleted from the\n"
                    . "// database as appropriate. For more details, see \"hook_civicrm_managed\" at:\n"
                    . "// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference";
                $mgdBuilder = new PhpData($ctx['apiCronFile'], $header);
                $mgdBuilder->set($mgdEntities);
                $mgdBuilder->save($ctx, $output);
            } else {
                $output->writeln(sprintf('<error>Skip %s: file already exists</error>', $ctx['apiCronFile']));
            }
        }

        $module = new Module($this->getContainer()->get('templating'));
        $module->loadInit($ctx);
        $module->save($ctx, $output);
    }
}
