<?php
namespace CRM\CivixBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Ini;
use CRM\CivixBundle\Utils\Path;

class ConfigSetCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('config:set')
            ->setDescription('Set configuration value')
            ->addArgument('key', InputArgument::REQUIRED, 'Parameter name')
            ->addArgument('value', InputArgument::REQUIRED, 'Parameter value')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ctx = array();
        $ext = new Collection();

        $output->writeln("<info></info>");
        $basedir = new Path(getenv('HOME'));
        $ext->builders['dirs'] = new Dirs(array(
            $basedir->string('.civix'),
        ));
        $ext->builders['ini'] = new Ini($basedir->string('.civix', 'civix.ini'));

        $ext->loadInit($ctx);
        $data = $ext->builders['ini']->get();
        if (!is_array($data)) {
            $data = array('parameters' => array());
        }
        $data['parameters'][$input->getArgument('key')] = $input->getArgument('value');
        $ext->builders['ini']->set($data);
        $ext->save($ctx, $output);

        \CRM\CivixBundle\Utils\Commands::createProcess('cache:clear --no-warmup')
            ->run(function ($type, $buffer) {
                echo $buffer;
            }
        );
    }

}
