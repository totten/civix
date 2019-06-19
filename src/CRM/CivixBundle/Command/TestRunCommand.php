<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Services;
use CRM\CivixBundle\Utils\Path;
use Symfony\Component\Console\Command\Command;
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
class TestRunCommand extends Command {
  const TIMEOUT = 1000;

  /**
   * The maximum number of seconds to allow the PHPUnit bootstrap file to persist
   */
  const BOOTSTRAP_TTL = 600;

  protected function configure() {
    $this
      ->setName('test')
      ->setDescription('Run a unit test (DEPRECATED)')
      ->addArgument('<TestClass>', InputArgument::OPTIONAL, 'Test class name (eg "CRM_Myextension_MyTest")')
      ->addOption('clear', NULL, InputOption::VALUE_NONE, 'Clear the cached PHPUnit bootstrap data')
      ->addOption('filter', NULL, InputOption::VALUE_REQUIRED, 'Restrict tests by name (regex)')
      ->addOption('configuration', 'c', InputOption::VALUE_NONE, 'Run all the tests as configured in the phpunit.xml in the root directory of your extension.')
      ->addOption('debug', NULL, InputOption::VALUE_NONE, 'Run PHPUnit in debug mode.');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    Services::boot(['output' => $output]);
    $basedir = new Path(getcwd());

    $output->writeln("<comment>Warning: 'civix test' deprecated.  Run phpunit4 directly from the extension directory instead.</comment>");

    // Find extension metadata
    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);
    if ($info->getType() != 'module') {
      $output->writeln('<error>Wrong extension type: ' . $attrs['type'] . '</error>');
      return;
    }

    // Find the main phpunit
    $civicrm_api3 = Services::api3();
    if (!$civicrm_api3 || !$civicrm_api3->local) {
      $output->writeln("<error>'test' requires access to local CiviCRM source tree. Configure civicrm_api3_conf_path.</error>");
      return;
    }
    global $civicrm_root;
    if (empty($civicrm_root) || !is_dir($civicrm_root)) {
      $output->writeln("<error>Failed to locate CiviCRM root path: $civicrm_root</error>");
      return;
    }
    $phpunit_bin = "$civicrm_root/tools/scripts/phpunit";
    if (!file_exists($phpunit_bin)) {
      $output->writeln("<error>Failed to locate PHPUnit:\n  $phpunit_bin</error>");
      $output->writeln("<error>Have you configured CiviCRM for testing? See also:\n  https://docs.civicrm.org/dev/en/latest/testing/#setup</error>");
      return;
    }
    $test_settings_path = "$civicrm_root/tests/phpunit/CiviTest/civicrm.settings.php";
    $test_settings_dist_path = "$civicrm_root/tests/phpunit/CiviTest/civicrm.settings.dist.php";
    if (!file_exists($test_settings_path) && !file_exists($test_settings_dist_path)) {
      $output->writeln("<error>Failed to locate test settings:\n  $test_settings_path</error>");
      $output->writeln("<error>Have you configured CiviCRM for testing? See also:\n  https://docs.civicrm.org/dev/en/latest/testing/#setup</error>");
      return;
    }
    if (file_exists($test_settings_path) && self::checkLegacyExtensionSettings($test_settings_path)) {
      $output->writeln("<comment>Warning: Possible conflicts in $test_settings_path</comment>");
      $output->writeln("<comment>The following options may conflict with civix-based testing: 'ext_repo_url', 'extensionsDir', and/or 'extensionsURL'.</comment>");
    }

    $phpunit_boot = $this->getBootstrapFile($info->getKey(), $input->getOption('clear'));
    if (empty($phpunit_boot) || !file_exists($phpunit_boot)) {
      $output->writeln("<error>Failed to create PHPUnit bootstrap file</error>");
      return;
    }

    $tests_dir = implode(DIRECTORY_SEPARATOR, [getcwd(), 'tests', 'phpunit']);

    // Prepare the command
    $command = [];
    $command[] = $phpunit_bin;
    $command[] = '--include-path';
    $command[] = $tests_dir;
    $command[] = '--bootstrap';
    $command[] = $phpunit_boot;
    $command[] = '--colors';
    if ($input->getOption('filter')) {
      $command[] = '--filter';
      $command[] = $input->getOption('filter');
    }
    if ($input->getOption('configuration')) {
      $command[] = '--configuration';
      $command[] = $basedir->string('phpunit.xml');
    }
    if ($input->getOption('debug')) {
      $command[] = '--debug';
    }
    $command[] = $input->getArgument('<TestClass>');

    // Run phpunit with our "tests" directory
    chdir("$civicrm_root/tools");
    $process = new Process(
      call_user_func_array(['\CRM\CivixBundle\Command\TestRunCommand', 'createPhpShellCommand'], $command),
      NULL, NULL, NULL, self::TIMEOUT
    );
    $process->run(function ($type, $buffer) use ($output) {
      $output->write($buffer);
    });
    $output->write("\n");
  }

  /**
   * Generate the shell statement for invoking a PHP script
   *
   * @code
   * $shellCommand = createPhpShellCommand('/path/to/my-script.php', 'argument 1', 'argument 2', ...);
   * @endCode
   *
   * @param string $script
   * @return string a valid shell command
   */
  protected static function createPhpShellCommand($script) {
    $php = escapeshellcmd(self::getPhp());
    $args = func_get_args(); // get $script and any others
    $escArgs = array_map('escapeshellarg', $args);
    $cmd = $php . ' ' . implode(' ', $escArgs);
    return $cmd;
  }

  protected static function getPhp() {
    $phpFinder = new PhpExecutableFinder();
    if (!$phpPath = $phpFinder->find()) {
      throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
    }
    return $phpPath;
  }

  /**
   * Determine whether $file contains any unnecessary extension settings
   *
   * @param string $file readable file path
   * @return bool TRUE if the file contains potentially conflicting settings
   */
  protected static function checkLegacyExtensionSettings($file) {
    $content = file_get_contents($file);
    if (preg_match('/civicrm_setting..Extension Preferences....ext_repo_url../', $content)) {
      return TRUE;
    }
    if (preg_match('/civicrm_setting..Directory Preferences....extensionsDir../', $content)) {
      return TRUE;
    }
    if (preg_match('/civicrm_setting..URL Preferences....extensionsURL../', $content)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Find (or auto-create) a PHP file with information for bootstrapping the test environment
   *
   * @param string $key the extension for which tests will be run
   * @return string temp file path
   */
  protected function getBootstrapFile($key, $clear = FALSE) {
    $cacheDir = Services::cacheDir();
    $file = $cacheDir->string("civix-phpunit.{$key}.php");
    if ($clear || !file_exists($file) || filemtime($file) < time() - self::BOOTSTRAP_TTL) {
      $template_vars = [];
      $template_vars['civicrm_setting'] = [];
      // disable extension searching
      $template_vars['civicrm_setting']['Extension Preferences']['ext_repo_url'] = FALSE;
      // use the same source tree for linked Civi runtime and test Civi runtime
      $template_vars['civicrm_setting']['Directory Preferences']['extensionsDir'] = \CRM_Core_BAO_Setting::getItem('Directory Preferences', 'extensionsDir');
      // extensionsURL of linked Civi runtime may differ from ideal value for test Civi runtime, but that's OK because extensionsURL defines *static* resources
      $template_vars['civicrm_setting']['URL Preferences']['extensionsURL'] = \CRM_Core_BAO_Setting::getItem('URL Preferences', 'extensionsURL');
      $template_vars['civicrm_setting']['Test']['test_extensions'] = array_keys(\CRM_Core_PseudoConstant::getExtensions());

      file_put_contents($file, Services::templating()
        ->render('phpunit-boot.php.php', $template_vars));
    }
    return $file;
  }

}
