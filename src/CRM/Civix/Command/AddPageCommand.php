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
use CRM\Civix\Builder\Menu;
use CRM\Civix\Builder\Module;
use CRM\Civix\Builder\Template;
use CRM\Civix\Utils\Path;
use Exception;

class AddPageCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('add-page')
            ->setDescription('Add a basic web page')
            ->addArgument('className', InputArgument::REQUIRED, 'Page class name (eg "MyPage")')
            ->addArgument('webPath', InputArgument::REQUIRED, 'The path which maps to this page (eg "civicrm/my-page"")')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!preg_match('/^civicrm\//', $input->getArgument('webPath'))) {
            throw new Exception("Web page path must begin with 'civicrm/'");
        }
    
        $ctx = array();
        $ctx['type'] = 'module';
        $ctx['basedir'] = rtrim(getcwd(),'/');
        $ctx['pageClassName'] = $input->getArgument('className');
        $basedir = new Path($ctx['basedir']);

        $info = new Info($basedir->string('info.xml'));
        $info->load($ctx);
        $attrs = $info->get()->attributes();
        if ($attrs['type'] != 'module') {
            $output->writeln('<error>Wrong extension type: '. $attrs['type'] . '</errror>');
            return;
        }
        
        $dirs = new Dirs(array(
            $basedir->string('xml','Menu'),
            $basedir->string($ctx['namespace'],'Page'),
            $basedir->string('templates', $ctx['namespace'],'Page'),
        ));
        $dirs->save($ctx, $output);
        
        $menu = new Menu($basedir->string('xml', 'Menu', $ctx['mainFile'] . '.xml'));
        $menu->loadInit($ctx);
        if (!$menu->hasPath($input->getArgument('webPath'))) {
            $menu->addItem($ctx, $input->getArgument('className'), $input->getArgument('webPath'));
            $menu->save($ctx, $output);
        } else {
            $output->writeln(sprintf('<error>Failed to bind %s to class %s; %s is already bound</error>',
                $input->getArgument('webPath'),
                $input->getArgument('className'),
                $input->getArgument('webPath')
            ));
        }
        
        $phpFile = $basedir->string($ctx['namespace'], 'Page', $ctx['pageClassName'] . '.php');
        if (!file_exists($phpFile)) {
            $output->writeln(sprintf('<info>Write %s</info>', $phpFile));
            file_put_contents($phpFile, $this->getContainer()->get('templateEngine')->render('page.php', $ctx));
        } else {
            $output->writeln(sprintf('<error>Skip %s: file already exists</error>', $phpFile));
        }
        
        $tplFile = $basedir->string('templates', $ctx['namespace'], 'Page', $ctx['pageClassName'] . '.tpl');
        if (!file_exists($tplFile)) {
            $output->writeln(sprintf('<info>Write %s</info>', $tplFile));
            file_put_contents($tplFile, $this->getContainer()->get('templateEngine')->render('page.tpl.php', $ctx));
        } else {
            $output->writeln(sprintf('<error>Skip %s: file already exists</error>', $tplFile));
        }
        
        $module = new Module($this->getContainer()->get('templateEngine'));
        $module->loadInit($ctx);
        $module->save($ctx, $output);
    }
}
