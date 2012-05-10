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
use CRM\Civix\Utils\Path;

class InitCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('init-module')
            ->setDescription('Create a new extension of type "module"')
            ->addArgument('fullName', InputArgument::REQUIRED, 'Qualified extension name (e.g. "com.example.myextension")')
            //->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Type of extension (e.g. "module", "payment", "report", "search")', 'module')
            //->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Type of extension', 'module')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ctx = array();
        $ctx['type'] = 'module';
        $ctx['fullName'] = $input->getArgument('fullName');
        $ctx['basedir'] = $ctx['fullName'];
        if (preg_match('/^[a-z0-9\.]+\.([a-z0-9]+)$/', $ctx['fullName'], $matches)) {
            $ctx['mainFile'] = $matches[1];
            $ctx['namespace'] = 'CRM/' . strtoupper($ctx['mainFile']{0}) . substr($ctx['mainFile'], 1);
        } else {
            $output->writeln('<error>Malformed package name</error>');
            return;
        }
        $ext = new Collection();
        
        $output->writeln("<info>Initalize module ".$ctx['fullName']."</info>");
        $basedir = new Path($ctx['basedir']);
        $ext->builders['dirs'] = new Dirs(array(
            $basedir->string('build'),
            $basedir->string('templates'),
            $basedir->string('xml'),
            $basedir->string($ctx['namespace']),
        ));
        $ext->builders['info'] = new Info($basedir->string('info.xml'));
        $ext->builders['module'] = new Module($this->getContainer()->get('templateEngine'));
        
        $ext->init($ctx);
        $ext->save($ctx, $output);
    }
}
