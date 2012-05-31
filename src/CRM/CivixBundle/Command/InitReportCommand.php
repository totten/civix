<?php
namespace CRM\CivixBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Command\BaseCommand;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;

class InitReportCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('init-report')
            ->setDescription('Create a new extension of type "report"')
            ->addArgument('fullName', InputArgument::REQUIRED, 'Qualified extension name (e.g. "com.example.myextension")')
            ->addArgument('component', InputArgument::REQUIRED, 'A component (CiviGrant, CiviCase, etc)')
            ->addOption('webPath', null, InputOption::VALUE_OPTIONAL, 'The path which maps to this report (eg "civicrm/report/x")')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ctx = array();
        $ctx['type'] = 'report';
        $ctx['typeInfo'] = array();
        $ctx['fullName'] = $input->getArgument('fullName');
        $ctx['basedir'] = $ctx['fullName'];
        if (preg_match('/^[a-z0-9\.]+\.([a-z0-9]+)$/', $ctx['fullName'], $matches)) {
            $ctx['mainFile'] = $matches[1];
            $ctx['pageClassName'] = $matches[1];
        } else {
            $output->writeln('<error>Malformed package name</error>');
            return;
        }
        
        $allowedComponents = array('CiviCase', 'CiviContribute', 'CiviEvent', 'CiviGrant', 'CiviMember');
        if (in_array($input->getArgument('component'), $allowedComponents)) {
            $ctx['typeInfo']['component'] = $input->getArgument('component');
        } else {
            throw new \Exception("Component must be one of: " . implode(', ', $allowedComponents));
        }
        
        if (preg_match('/^civicrm\/report\/(.*)$/', $input->getOption('webPath'), $matches)) {
            $ctx['typeInfo']['reportUrl'] = $matches[1];
        } else {
            $ctx['typeInfo']['reportUrl'] = $ctx['fullName'];
        }
        
        
        $ext = new Collection();
        
        $output->writeln("<info>Initalize report ".$ctx['fullName']."</info>");
        $basedir = new Path($ctx['basedir']);
        $ext->builders['dirs'] = new Dirs(array(
            $basedir->string('build'),
            $basedir->string('templates'),
        ));
        $ext->builders['info'] = new Info($basedir->string('info.xml'));
      
        $phpFile = $basedir->string($ctx['pageClassName'] . '.php');
        $ext->builders['report.php'] = new Template('CRMCivixBundle:Code:report.php.php', $phpFile, FALSE, $this->getContainer()->get('templating'));
        
        $tplFile = $basedir->string('templates', $ctx['pageClassName'] . '.tpl');
        $ext->builders['page.tpl.php'] = new Template('CRMCivixBundle:Code:report.tpl.php', $tplFile, FALSE, $this->getContainer()->get('templating'));

        $ext->init($ctx);
        $ext->save($ctx, $output);
    }
}
