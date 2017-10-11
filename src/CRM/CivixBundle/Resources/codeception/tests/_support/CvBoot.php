<?php

/**
 * Call the "cv" command.
 *
 * @param string $cmd
 *   The rest of the command to send.
 * @param string $decode
 *   Ex: 'json' or 'phpcode'.
 * @return string
 *   Response output (if the command executed normally).
 * @throws \RuntimeException
 *   If the command terminates abnormally.
 */
function cv($cmd, $decode = 'json') {
  return _cv_run("cv $cmd", $decode);
}

function _cv_run($cmd, $decode = 'json') {
  $descriptorSpec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => STDERR);
  $oldOutput = getenv('CV_OUTPUT');
  putenv("CV_OUTPUT=json");
  $process = proc_open($cmd, $descriptorSpec, $pipes, __DIR__);
  putenv("CV_OUTPUT=$oldOutput");
  fclose($pipes[0]);
  $result = stream_get_contents($pipes[1]);
  fclose($pipes[1]);
  if (proc_close($process) !== 0) {
    throw new RuntimeException("Command failed ($cmd):\n$result");
  }
  switch ($decode) {
    case 'raw':
      return $result;

    case 'phpcode':
      // If the last output is /*PHPCODE*/, then we managed to complete execution.
      if (substr(trim($result), 0, 12) !== "/*BEGINPHP*/" || substr(trim($result), -10) !== "/*ENDPHP*/") {
        throw new \RuntimeException("Command failed ($cmd):\n$result");
      }
      return $result;

    case 'json':
      return json_decode($result, 1);

    default:
      throw new RuntimeException("Bad decoder format ($decode)");
  }
}

/**
 * @file
 *
 * The CvBoot extension uses the "cv" command line helper to bootstrap CiviCRM.
 *
 * @code
 * ## codeception.yml
 * extensions:
 *     enabled:
 *         - CvBoot
 * @endcode
 *
 * Note: If you use PhpBrowser or WebDriver, leave the default URL value of
 * 'http://localhost/myapp'. CvBoot will automatically replace this with the
 * actual URL of the target CiviCRM instance.
 *
 * Advanced options:
 *   - To customize bootstrap, set "extensions.config.CvBoot.command" to something like
 *     - "cv php:boot --level=classloader"
 *     - "cv php:boot --level=settings --test"
 *     - "cv php:boot --level=full"
 *   - To change the dummy/placeholder URL, set "extensions.config.CvBoot.dummy_url" to a different URL ("http://newdummy.example").
 */
class CvBoot extends \Codeception\Extension {
  const DEFAULT_DUMMY_URL = 'http://localhost/myapp';

  public static $events = array(
    'suite.before' => 'beforeSuite',
    'test.before' => 'beforeTest',
  );

  public static $defaults = array(
    // How far to go in bootstrapping Civi?
    'command' => 'cv php:boot --level=settings',

    // If any acceptance tests ar configured for dummy_url, they
    // will be updated with the real URL.
    'dummy_url' => 'http://localhost/myapp',
  );

  private $startUrl = NULL;

  public function beforeSuite(\Codeception\Event\SuiteEvent $e) {
    $this->boot();
  }

  public function beforeTest(\Codeception\Event\TestEvent $e) {
    if (in_array('PhpBrowser', $this->getCurrentModuleNames())) {
      /** @var \Codeception\Module\PhpBrowser $phpBrowser */
      $phpBrowser = $this->getModule('PhpBrowser');
      if ($this->isDummyUrl($phpBrowser->_getConfig('url'))) {
        //$this->writeln("\n\ALTER PhpBrowser.url\n\n");
        $phpBrowser->_reconfigure(array(
          'url' => $this->getStartUrl(),
        ));
      }
    }
    if (in_array('WebDriver', $this->getCurrentModuleNames())) {
      /** @var \Codeception\Module\WebDriver $webDriver */
      $webDriver = $this->getModule('WebDriver');
      if ($this->isDummyUrl($webDriver->_getConfig('url'))) {
        //$this->writeln("\n\ALTER WebDriver.url\n\n");
        $webDriver->_reconfigure(array(
          'url' => $this->getStartUrl(),
        ));
      }
    }
  }

  protected function boot() {
    static $booted = FALSE;
    if ($booted) {
      return;
    }
    $booted = TRUE;

    $extConfig = $this->getExtConfig();
    if (!empty($extConfig['command'])) {
      //$this->writeln("\n\nBOOT\n\n");
      eval(_cv_run($extConfig['command'], 'phpcode'));
    }
  }

  protected function getExtConfig() {
    $config = $this->getGlobalConfig();
    $extConfig = self::$defaults;
    if (isset($config['extensions']['config']['CvBoot'])) {
      $extConfig = array_merge($extConfig, $config['extensions']['config']['CvBoot']);
    }
    return $extConfig;
  }

  protected function getStartUrl() {
    if ($this->startUrl === NULL) {
      $this->startUrl = cv('url');
    }
    return $this->startUrl;
  }

  protected function isDummyUrl($url) {
    $extConfig = $this->getExtConfig();
    return $url === $extConfig['dummy_url'];
  }

}
