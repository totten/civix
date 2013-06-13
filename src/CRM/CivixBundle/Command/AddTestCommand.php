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

class AddTestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('generate:test')
            ->setDescription('Add a new unit-test class to CiviCRM Extension')
            ->addArgument('<CRM_Full_ClassName>', InputArgument::REQUIRED, 'The full class name (eg "CRM_Myextension_MyTest")')
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
            $output->writeln('<error>Wrong extension type: '. $attrs['type'] . '</error>');
            return;
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $input->getArgument('<CRM_Full_ClassName>'))) {
            throw new Exception("Class name must be alphanumeric (with underscores)");
        }
        if (!preg_match('/Test$/', $input->getArgument('<CRM_Full_ClassName>'))) {
            throw new Exception("Class name must end with the word \"Test\"");
        }

        $ctx['testClass'] = $input->getArgument('<CRM_Full_ClassName>');
        $ctx['testFile'] = strtr($ctx['testClass'], '_', '/') . '.php';
        $ctx['testPath'] = $basedir->string('tests', 'phpunit', $ctx['testFile']);

        $dirs = new Dirs(array(
            dirname($ctx['testPath'])
        ));
        $dirs->save($ctx, $output);

        if (!file_exists($ctx['testPath'])) {
            $output->writeln(sprintf('<info>Write %s</info>', $ctx['testPath']));
            file_put_contents($ctx['testPath'], $this->getContainer()->get('templating')->render('CRMCivixBundle:Code:test.php.php', $ctx));
        } else {
            $output->writeln(sprintf('<error>Skip %s: file already exists</error>', $ctx['testPath']));
        }
    }
}
