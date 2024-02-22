<?php

namespace CRM\CivixBundle\Utils;

class PathloadPackage {

  /**
   * Split a package identifier into its parts.
   *
   * @param string $package
   *   Ex: 'foobar@1.2.3'
   * @return array
   *   Tuple: [$majorName, $name, $version]
   *   Ex: 'foobar@1', 'foobar', '1.2.3'
   */
  public static function parseExpr(string $package): array {
    if (strpos($package, '@') === FALSE) {
      throw new \RuntimeException("Malformed package name: $package");
    }
    [$prefix, $suffix] = explode('@', $package, 2);
    $prefix = str_replace('/', '~', $prefix);
    [$major] = explode('.', $suffix, 2);
    return ["$prefix@$major", $prefix, $suffix];
  }

  public static function parseFileType(string $file): array {
    if (substr($file, -4) === '.php') {
      return ['php', substr(basename($file), 0, -4)];
    }
    elseif (substr($file, '-5') === '.phar') {
      return ['phar', substr(basename($file), 0, -5)];
    }
    elseif (is_dir($file)) {
      return ['dir', basename($file)];
    }
    else {
      return [NULL, NULL];
    }
  }

  /**
   * @param string $file
   *  Ex: '/var/www/app-1/lib/foobar@.1.2.3.phar'
   * @return PathLoadPackage|null
   */
  public static function create(string $file): ?PathLoadPackage {
    [$type, $base] = self::parseFileType($file);
    if ($type === NULL) {
      return NULL;
    }
    $self = new PathLoadPackage();
    [$self->majorName, $self->name, $self->version] = static::parseExpr($base);
    $self->file = $file;
    $self->type = $type;
    return $self;
  }

  /**
   * @var string
   *   Ex: '/var/www/app-1/lib/cloud-file-io@1.2.3.phar'
   */
  public $file;

  /**
   * @var string
   *   Ex: 'cloud-file-io'
   */
  public $name;

  /**
   * @var string
   *   Ex: 'cloud-file-io@1'
   */
  public $majorName;

  /**
   * @var string
   *   Ex: '1.2.3'
   */
  public $version;

  /**
   * @var string
   *   Ex: 'php' or 'phar' or 'dir'
   */
  public $type;

}
