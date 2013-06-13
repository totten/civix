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
use CRM\CivixBundle\Builder\CustomDataXML;
use CRM\CivixBundle\Utils\Path;
use Exception;

class AddCustomDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('generate:custom-data')
            ->setDescription('Export custom data to an XML file')
            ->addArgument('<CustomGroupIds>', InputArgument::REQUIRED, 'Comma-separated list of custom group IDs (from linked dev site)')
            ->addArgument('<CustomDataFile.xml>', InputArgument::OPTIONAL, 'The path to write custom data to (default: xml/auto_install.xml)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // load Civi to get access to civicrm_api_get_function_name
        $civicrm_api3 = $this->getContainer()->get('civicrm_api3');
        if (!$civicrm_api3 || !$civicrm_api3->local) {
            $output->writeln("<error>generate:custom-data requires access to local CiviCRM instance. Configure civicrm_api3_conf_path.</error>");
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

        $dirs = new Dirs(array(
            $basedir->string('xml'),
        ));
        $dirs->save($ctx, $output);

        if ($input->getArgument('<CustomDataFile.xml>')) {
            $customDataXMLFile = $basedir->string($input->getArgument('<CustomDataFile.xml>'));
        } else {
            $customDataXMLFile = $basedir->string('xml', 'auto_install.xml');
        }
        $customDataXML = new CustomDataXML(explode(',', $input->getArgument('<CustomGroupIds>')), $customDataXMLFile, $input->getOption('force'));
        $customDataXML->save($ctx, $output);

        if (preg_match('/\/xml\/.*_install.xml$/', $customDataXMLFile)) {
            $output->writeln(" * NOTE: This filename ends with \"_install.xml\". If you would like to load it automatically on new sites, then make sure there is an install/upgrade class (i.e. run \"civix generate:upgrader\"");
        } else {
            $output->writeln(" * NOTE: By default, this file will not be loaded automatically -- you must define installation or upgrade logic to load the file.");
        }
    }
}
