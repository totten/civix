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
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;

class InitReportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('generate:report-ext')
            ->setDescription('Create a new CiviCRM Report-Extension')
            ->addArgument('<full.ext.name>', InputArgument::REQUIRED, 'Qualified extension name (e.g. "com.example.myextension")')
            ->addArgument('<CiviComponent>', InputArgument::REQUIRED, 'A component (CiviGrant, CiviCase, etc)')
            ->addOption('webPath', null, InputOption::VALUE_OPTIONAL, 'The path which maps to this report (eg "civicrm/report/x")')
            ->addOption('copy', null, InputOption::VALUE_OPTIONAL, 'The class name of an existing report which should be copied')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ctx = array();
        $ctx['type'] = 'report';
        $ctx['typeInfo'] = array();
        $ctx['fullName'] = $input->getArgument('<full.ext.name>');
        $ctx['basedir'] = $ctx['fullName'];
        $ctx['reportClassName'] = preg_replace(':\.:','_',$ctx['fullName']);
        if (preg_match('/^[a-z0-9\.]+\.([a-z0-9]+)$/', $ctx['fullName'], $matches)) {
            $ctx['mainFile'] = $matches[1];
        } else {
            $output->writeln('<error>Malformed package name</error>');
            return;
        }

        if (in_array($input->getArgument('<CiviComponent>'), $this->getReportComponents())) {
            $ctx['typeInfo']['component'] = $input->getArgument('<CiviComponent>');
        } else {
            throw new \Exception("Component must be one of: " . implode(', ', $this->getReportComponents()));
        }

        if (preg_match('/^civicrm\/report\/(.*)$/', $input->getOption('webPath'), $matches)) {
            $ctx['typeInfo']['reportUrl'] = $matches[1];
        } else {
            $ctx['typeInfo']['reportUrl'] = $ctx['fullName'];
        }

        $ext = new Collection();

        $output->writeln("<info>Initialize report ".$ctx['fullName']."</info>");
        $basedir = new Path($ctx['basedir']);
        $ext->builders['dirs'] = new Dirs(array(
            $basedir->string('build'),
            $basedir->string('templates'),
        ));
        $ext->builders['info'] = new Info($basedir->string('info.xml'));

        $phpFile = $basedir->string($ctx['mainFile'] . '.php');
        $tplFile = $basedir->string('templates', $ctx['mainFile'] . '.tpl');
        if ($srcClassName = $input->getOption('copy')) {
            // we need bootstrap to set up include path to locate file -- but that's it
            $civicrm_api3 = $this->getContainer()->get('civicrm_api3');
            if (!$civicrm_api3 || !$civicrm_api3->local) {
              $output->writeln("<error>--copy requires access to local CiviCRM source tree. Configure civicrm_api3_conf_path.</error>");
              return;
            }

            $origTplFile = 'templates/' . preg_replace('/_/','/', $srcClassName) . '.tpl';
            $ext->builders['report.php'] = new CopyClass($srcClassName, $ctx['reportClassName'], $phpFile, FALSE);
            $ext->builders['page.tpl.php'] = new CopyFile($origTplFile, $tplFile, FALSE);
        } else {
            $ext->builders['report.php'] = new Template('CRMCivixBundle:Code:report.php.php', $phpFile, FALSE, $this->getContainer()->get('templating'));
            $ext->builders['page.tpl.php'] = new Template('CRMCivixBundle:Code:report.tpl.php', $tplFile, FALSE, $this->getContainer()->get('templating'));
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
