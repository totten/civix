<?php
namespace CRM\CivixBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Run unit tests using CiviCRM's copy of PHPUnit
 *
 * This basically involves a couple things:
 *  - Locate CiviCRM's copy of PHPUnit
 *  - Locate the extensions test directory
 *  - Call CiviCRM's copy of PHPUnit while adding the test directory to the include path
 */
class TestRunCommand extends ContainerAwareCommand
{
    const TIMEOUT = 1000;
    protected function configure()
    {
        $this
            ->setName('test')
            ->setDescription('Run a unit test')
            ->addArgument('testClass', InputArgument::REQUIRED, 'Test class name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Find the main phpunit
        $civicrm_api3 = $this->getContainer()->get('civicrm_api3');
        if (!$civicrm_api3 || !$civicrm_api3->local) {
            $output->writeln("<error>'test' requires access to local CiviCRM source tree. Configure civicrm_api3_conf_path.</error>");
            return;
        }
        global $civicrm_root;
        if (empty($civicrm_root) || !is_dir($civicrm_root)) {
            $output->writeln("<error>Failed to locate CiviCRM root path: $civicrm_root</error>");
            return;
        }
        $test_settings_path = "$civicrm_root/tests/phpunit/CiviTest/civicrm.settings.php";
        if (! file_exists($test_settings_path)) {
            $output->writeln("<error>Failed to locate test settings:\n  $test_settings_path</error>");
            $output->writeln("<error>Have you configured CiviCRM for testing? See also:\n  http://wiki.civicrm.org/confluence/display/CRM/Setting+up+your+personal+testing+sandbox+HOWTO</error>");
            return;
        }
        $phpunit_bin = "$civicrm_root/tools/scripts/phpunit";
        if (! file_exists($phpunit_bin)) {
            $output->writeln("<error>Failed to locate PHPUnit:\n  $phpunit_bin</error>");
            $output->writeln("<error>Have you configured CiviCRM for testing? See also:\n  http://wiki.civicrm.org/confluence/display/CRM/Setting+up+your+personal+testing+sandbox+HOWTO</error>");
            return;
        }

        // Run phpunit with our "tests" directory
        $tests_dir = implode(DIRECTORY_SEPARATOR, array(getcwd(), 'tests', 'phpunit'));
        chdir("$civicrm_root/tools");
        $process = new Process(
            self::createPhpShellCommand($phpunit_bin, '--include-path', $tests_dir, $input->getArgument('testClass')),
            null, null, null, self::TIMEOUT
        );
        $process->run(function ($type, $buffer) use ($output) {
            $output->write($buffer);
        });
        $output->write("\n");
    }

    /**
     * Generate the shell statement for invoking a PHP script
     *
     * @param $script
     * @return string a valid shell command
     */
    protected static function createPhpShellCommand($script)
    {
        $php = escapeshellcmd(self::getPhp());
        $args = func_get_args(); // get $script and any others
        $escArgs = array_map('escapeshellarg', $args);
        $cmd = $php.' '.implode(' ', $escArgs);
        return $cmd;
    }

    protected static function getPhp()
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find()) {
            throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }
        return $phpPath;
    }
}
