<?php
namespace CRM\Civix\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\Civix\Extension;

class InitCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Create a new extension')
            ->addArgument('full-name', InputArgument::REQUIRED, 'Qualified extension name (e.g. "com.example.myextension")')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Type of extension (e.g. "module", "payment", "report", "search")', 'module')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fullName = $input->getArgument('full-name');
        if (preg_match('/^[a-z0-9\.]+\.([a-z0-9]+)$/', $fullName, $matches)) {
            $mainFile = $matches[1];
            $namespace = strtoupper($mainFile{0}) . substr($mainFile, 1);
        } else {
            $output->writeln('<error>Malformed package name</error>');
            return;
        }
        
        switch($input->getOption('type')) {
          case 'module':
          case 'payment':
          case 'report':
          case 'search':
              break;
          default:
              $output->writeln("<error>Unrecognized extension type: ". $input->getOption('type'). "</error>");
              return;
        }
        
        $output->writeln("<info>Initalize ${fullName} [${mainFile}.php]</info>");
        $ext = new Extension($fullName, $mainFile, $namespace, $input->getOption('type'), $fullName);
        
        foreach ($ext->getDirs() as $dir) {
            if (!is_dir($dir)) {
                $output->writeln("<info>Create ${dir}</info>");
                mkdir($dir);
            } else {
                $output->writeln("<comment>Found ${dir}</comment>");
            }
        }
        
        $ext->saveXml();
    }
}
