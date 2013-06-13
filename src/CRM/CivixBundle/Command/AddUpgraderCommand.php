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
use CRM\CivixBundle\Builder\Menu;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;
use Exception;

class AddUpgraderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('generate:upgrader')
            ->setDescription('Add an example upgrader class to a CiviCRM Module-Extension')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TODO validate that hook_civicrm_upgrade has been implemented

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
            $basedir->string('sql'),
            $basedir->string($ctx['namespace']),
            $basedir->string($ctx['namespace'],'Upgrader'),
        ));
        $dirs->save($ctx, $output);

        $phpFile = $basedir->string($ctx['namespace'], 'Upgrader.php');
        if (!file_exists($phpFile)) {
            $output->writeln(sprintf('<info>Write %s</info>', $phpFile));
            file_put_contents($phpFile, $this->getContainer()->get('templating')->render('CRMCivixBundle:Code:upgrader.php.php', $ctx));
        } else {
            $output->writeln(sprintf('<error>Skip %s: file already exists, defer to customized version</error>', $phpFile));
        }

        $phpFile = $basedir->string($ctx['namespace'], 'Upgrader', 'Base.php');
        $output->writeln(sprintf('<info>Write %s</info>', $phpFile));
        file_put_contents($phpFile, $this->getContainer()->get('templating')->render('CRMCivixBundle:Code:upgrader-base.php.php', $ctx));

        $module = new Module($this->getContainer()->get('templating'));
        $module->loadInit($ctx);
        $module->save($ctx, $output);
    }
}
