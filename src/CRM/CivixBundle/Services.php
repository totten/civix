<?php
namespace CRM\CivixBundle;

use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\Loader\FilesystemLoader;


class Services {

  protected static $cache;

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
}