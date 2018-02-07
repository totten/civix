<?php


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
