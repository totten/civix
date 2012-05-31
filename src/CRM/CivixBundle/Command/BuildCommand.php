<?php
namespace CRM\CivixBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use CRM\CivixBundle\Command\BaseCommand;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Menu;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;
use Exception;

class BuildCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Build a zip file for this extension')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
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
        
        $ctx['zipFile'] = $basedir->string('build', $ctx['fullName'] . '.zip');
        $cmdArgs = array(
            '-r',
            $ctx['zipFile'],
            $ctx['fullName'],
            '--exclude',
            'build/*',
            '*~',
            '*.bak'
        );
        $cmd = 'zip ' . implode(' ', array_map('escapeshellarg', $cmdArgs));
        
        chdir($basedir->string('..'));
        $process = new Process($cmd);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput());
        }
        print $process->getOutput();
    }
}
