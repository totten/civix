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
use CRM\CivixBundle\Utils\Path;

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
        $ext->builders['module'] = new Module($this->getContainer()->get('templating'));
        
        $ext->init($ctx);
        $ext->save($ctx, $output);
    }
}
