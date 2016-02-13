<?php
namespace CRM\CivixBundle;

use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\Loader\FilesystemLoader;


class Services {

  protected static $cache;

  public static function boot() {
    if (!isset(self::$cache['boot'])) {
      // TODO: Copy in Civi\Bootstrap() class.
      $cwd = getcwd();
      eval(Cv::call('php:boot --level=full', 'phpcode'));
      chdir($cwd);
      self::$cache['boot'] = 1;
    }
  }

  /**
   * @return \civicrm_api3
   */
  public static function api3() {
    if (!isset(self::$cache['civicrm_api3'])) {
      self::boot();
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
      $file = getenv('HOME') . '/.civix/civix.ini';
      if (file_exists($file)) {
        self::$cache['config'] = parse_ini_file($file, TRUE);
      }
      else {
        self::$cache['config'] = array();
      }
    }
    return self::$cache['config'];
  }

}