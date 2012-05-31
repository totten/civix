<?php
namespace CRM\Civix\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\Civix\Command\BaseCommand;
use CRM\Civix\Builder\Collection;
use CRM\Civix\Builder\Dirs;
use CRM\Civix\Builder\Info;
use CRM\Civix\Builder\Module;
use CRM\Civix\Builder\Template;
use CRM\Civix\Utils\Path;

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
            throw new Exception("Component must be one of: " . implode(', ', $allowedComponents));
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
        $ext->builders['report.php'] = new Template('report.php', $phpFile, FALSE, $this->getContainer()->get('templateEngine'));
        
        $tplFile = $basedir->string('templates', $ctx['pageClassName'] . '.tpl');
        $ext->builders['page.tpl.php'] = new Template('report.tpl.php', $tplFile, FALSE, $this->getContainer()->get('templateEngine'));

        $ext->init($ctx);
        $ext->save($ctx, $output);
    }
}
