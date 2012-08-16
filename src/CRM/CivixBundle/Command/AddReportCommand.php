<?php
namespace CRM\CivixBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\CopyClass;
use CRM\CivixBundle\Builder\CopyFile;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Builder\PhpData;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;

class AddReportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('generate:report')
            ->setDescription('Add a report to a module-extension')
            ->addArgument('className', InputArgument::REQUIRED, 'Report class name (eg "MyReport")')
            ->addArgument('component', InputArgument::REQUIRED, 'CiviCRM Component (' . implode(', ', $this->getReportComponents()) . ')')
            ->addOption('webPath', null, InputOption::VALUE_OPTIONAL, 'Path which maps to this report (eg "civicrm/report/my-report")')
            ->addOption('copy', null, InputOption::VALUE_OPTIONAL, 'Full class name of an existing report which should be copied (eg "CRM_Report_Form_Activity")')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //// Figure out template data ////
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

        if (!in_array($input->getArgument('component'), $this->getReportComponents())) {
            throw new \Exception("Component must be one of: " . implode(', ', $this->getReportComponents()));
        }

        $ctx['reportClassName'] = strtr($ctx['namespace'], '/', '_') . '_Form_Report_' . $input->getArgument('className');
        $ctx['reportClassFile'] = $basedir->string(strtr($ctx['reportClassName'], '_', '/') . '.php');
        $ctx['reportMgdFile'] = $basedir->string(strtr($ctx['reportClassName'], '_', '/') . '.mgd.php');
        $ctx['reportTplFile'] = $basedir->string('templates', strtr($ctx['reportClassName'], '_', '/') . '.tpl');

        $webPath = $input->getOption('webPath');
        if (!empty($webPath)) {
            if (preg_match('/^civicrm\/report\/(.+)$/', $webPath, $matches)) {
                $ctx['reportUrl'] = strtolower($matches[1]);
            } else {
                throw new \Exception("webPath must begin with \"civicrm/report/\"");
            }
        } else {
            $ctx['reportUrl'] = strtolower($ctx['fullName'] . '/' . $input->getArgument('className'));
        }

        //// Construct files ////
        $output->writeln("<info>Initialize report ".$ctx['reportClassName']."</info>");

        $ext = new Collection();
        $ext->builders['dirs'] = new Dirs(array(
            dirname($ctx['reportClassFile']),
            dirname($ctx['reportMgdFile']),
            dirname($ctx['reportTplFile']),
        ));

        if (!file_exists($ctx['reportMgdFile'])) {
            $mgdEntities = array(
              array(
                'name' => $ctx['reportClassName'],
                'entity' => 'ReportTemplate',
                'params' => array(
                  'version' => 3,
                  'label' => $input->getArgument('className'),
                  'description' => sprintf("%s (%s)", $input->getArgument('className'), $ctx['fullName']),
                  'class_name' => $ctx['reportClassName'],
                  'report_url' => $ctx['reportUrl'],
                  'component' => $input->getArgument('component'),
                ),
              ),
            );
            $header = "// This file declares a managed database record of type \"ReportTemplate\".\n"
                . "// The record will be automatically inserted, updated, or deleted from the\n"
                . "// database as appropriate. For more details, see \"hook_civicrm_managed\" at:\n"
                . "// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference";
            $ext->builders['mgd.php'] = new PhpData($ctx['reportMgdFile'], $header);
            $ext->builders['mgd.php']->set($mgdEntities);
        }

        if ($srcClassName = $input->getOption('copy')) {
            // we need bootstrap to set up include path to locate file -- but that's it
            $civicrm_api3 = $this->getContainer()->get('civicrm_api3');
            if (!$civicrm_api3 || !$civicrm_api3->local) {
              $output->writeln("<error>--copy requires access to local CiviCRM source tree. Configure civicrm_api3_conf_path.</error>");
              return;
            }

            $origTplFile = 'templates/' . preg_replace('/_/','/', $srcClassName) . '.tpl';
            $ext->builders['report.php'] = new CopyClass($srcClassName, $ctx['reportClassName'], $ctx['reportClassFile'], FALSE);
            $ext->builders['page.tpl.php'] = new CopyFile($origTplFile, $ctx['reportTplFile'], FALSE);
        } else {
            $ext->builders['report.php'] = new Template('CRMCivixBundle:Code:report.php.php', $ctx['reportClassFile'], FALSE, $this->getContainer()->get('templating'));
            $ext->builders['page.tpl.php'] = new Template('CRMCivixBundle:Code:report.tpl.php', $ctx['reportTplFile'], FALSE, $this->getContainer()->get('templating'));
        }

        $ext->init($ctx);
        $ext->save($ctx, $output);
    }

    /**
     * Get list of components that reports can be associated with
     *
     * @return array(string)
     */
    protected function getReportComponents() {
        return array(
            'CiviCampaign',
            'CiviCase',
            'CiviContribute',
            'CiviEvent',
            'CiviGrant',
            'CiviMail',
            'CiviMember',
            'CiviPledge',
            'Contact',
        );
    }
}
