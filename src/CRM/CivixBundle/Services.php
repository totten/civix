<?php
namespace CRM\CivixBundle;

use Civi\Cv\Bootstrap;
use CRM\CivixBundle\Utils\Path;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\Loader\FilesystemLoader;


class Services {

  protected static $cache;

  public static function boot($options = []) {
    if (!isset(self::$cache['boot'])) {
      $cwd = getcwd();
      $options = array_merge(['prefetch' => FALSE], $options);
      Bootstrap::singleton()->boot($options);
      \CRM_Core_Config::singleton();
      \CRM_Utils_System::loadBootStrap([], FALSE);
      chdir($cwd);
      self::$cache['boot'] = 1;
    }
  }

  /**
   * Get a reference to the API class.
   *
   * Pre-requisite: call boot() to startup CiviCRM.
   *
   * @return \civicrm_api3
   */
  public static function api3() {
    if (!isset(self::$cache['civicrm_api3'])) {
      if (!stream_resolve_include_path('api/class.api.php')) {
        throw new \RuntimeException("Booted CiviCRM, but failed to find 'api/class.api.php'");
      }
      require_once 'api/class.api.php';
      self::$cache['civicrm_api3'] = new \civicrm_api3();
    }
    return self::$cache['civicrm_api3'];
  }

  /**
   * @return EngineInterface
   */
  public static function templating() {
    if (!isset(self::$cache['templating'])) {
      $loader = new FilesystemLoader(__DIR__ . '/Resources/views/Code/%name%');
      self::$cache['templating'] = new PhpEngine(new TemplateNameParser(), $loader);
    }
    return self::$cache['templating'];
  }

  /**
   * Read any config data (~/.civix/civix.ini).
   *
   * @return array
   */
  public static function config() {
    if (!isset(self::$cache['config'])) {
      $file = self::configDir()->string('civix.ini');
      if (file_exists($file)) {
        self::$cache['config'] = parse_ini_file($file, TRUE);
      }
      else {
        self::$cache['config'] = [];
      }
    }
    return self::$cache['config'];
  }

  /**
   * @return Path
   */
  public static function configDir() {
    if (!isset(self::$cache['configDir'])) {
      $homes = [
        getenv('HOME'), // Unix
        getenv('USERPROFILE'), // Windows
      ];
      foreach ($homes as $home) {
        if (!empty($home)) {
          self::$cache['configDir'] = new Path($home . '/.civix');
          break;
        }
      }
      if (empty($home)) {
        throw new \RuntimeException('Failed to locate home directory. Please set HOME (Unix) or USERPROFILE (Windows).');
      }
    }
    return self::$cache['configDir'];
  }

  /**
   * @return Path
   */
  public static function cacheDir() {
    if (!isset(self::$cache['cacheDir'])) {
      self::$cache['cacheDir'] = self::configDir()->path('cache');
    }
    return self::$cache['cacheDir'];
  }

}
