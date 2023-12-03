<?php

namespace CRM\CivixBundle\Utils;

class Naming {

  /**
   * Determine if an extension's full symbolic name is well-formed.
   *
   * @param string $fullName
   *   Ex: 'org.example.foo'.
   * @return bool
   */
  public static function isValidFullName($fullName) {
    // Primarily alphanumeric, with limited symbols
    return preg_match('/^[a-z][-_a-z0-9\.]*$/', $fullName)
      // Prohibit double symbols
      && !preg_match('/[-_\.][-_\.]/', $fullName)
      // Prohibit terminal symbols
      && !preg_match('/[-_\.]$/', $fullName);
  }

  /**
   * Based on a full name, determine a short name.
   *
   * @param string $fullName
   *   Ex: 'org.example.foo'
   *   Ex: 'foo-bar'
   * @return string
   *   Ex: 'foo'
   *   Ex: 'foo_bar'
   */
  public static function createShortName($fullName) {
    // If the extension name has a . in it, only use the end of the extension
    // name for the short name
    $nameParts = explode('.', $fullName);
    $shortName = end($nameParts);
    $shortName = str_replace('-', '_', $shortName);

    return $shortName;
  }

  /**
   * Based on a full name, determine a camel case name.
   *
   * @param string $fullName
   *   Ex: 'org.example.foo'
   *   Ex: 'foo-bar'
   * @param string $delimiter
   * @return string
   *   Ex: 'Foo'
   *   Ex: 'FooBar'
   */
  public static function createCamelName($fullName, $delimiter = '_') {
    $shortName = self::createShortName($fullName);
    $camelCase = '';
    foreach (explode($delimiter, $shortName) as $shortNamePart) {
      $camelCase .= ucfirst($shortNamePart);
    }
    return $camelCase;
  }

  /**
   * Generate a table name for an entity.
   *
   * @param string $entity
   *   Ex: 'FooBar'
   * @return string
   *   Ex: 'civicrm_foo_bar'
   */
  public static function createTableName($entity) {
    return 'civicrm_' . strtolower(implode('_', array_filter(preg_split('/(?=[A-Z])/', $entity))));
  }

  /**
   * Construct a class name, within some namespace.
   *
   * @param string $namespace
   *   Ex: 'CRM_Foobar' or 'Civi\Foobar'.
   * @param string[] ...$suffixes
   *   Ex: ['Page', 'Hello']
   * @return string
   *   Ex: 'CRM_Foobar_Page_Hello' or 'Civi\Foobar\Page\Hello'
   */
  public static function createClassName(string $namespace, ...$suffixes): string {
    $delim = substr($namespace, 0, 4) === 'CRM/' ? '_' : '\\';
    $parts = [$namespace];
    foreach ($suffixes as $suffix) {
      $parts = array_merge($parts, is_array($suffix) ? $suffix : [$suffix]);
    }
    return str_replace('/', $delim, implode($delim, $parts));
  }

  /**
   * Construct a class file, within some namespace.
   *
   * @param string $namespace
   *   Ex: 'CRM_Foobar' or 'Civi\Foobar'.
   * @param string[] ...$suffixes
   *   Ex: ['Page', 'Hello']
   * @return string
   *   Ex: 'CRM/Foobar/Page/Hello.php' or 'Civi/Foobar/Page/Hello.php'
   */
  public static function createClassFile(string $namespace, ...$suffixes): string {
    return preg_replace(';[_/\\\];', '/', static::createClassName($namespace, ...$suffixes)) . '.php';
  }

  public static function createServiceName(string $namespace, ...$suffixes): string {
    $namespace = preg_replace(';^(CRM|Civi)[_/\\\];', '', $namespace);
    $parts = [lcfirst($namespace)];
    foreach ($suffixes as $suffix) {
      $parts[] = lcfirst($suffix);
    }
    return implode('.', $parts);
  }

  public static function removeServicePrefix(string $namespace, string $serviceName) {
    $defaultPrefix = Naming::createServiceName($namespace);
    $defaultPrefixLen = mb_strlen($defaultPrefix);
    return (mb_substr($serviceName, 0, $defaultPrefixLen) === $defaultPrefix)
      ? mb_substr($serviceName, 1 + $defaultPrefixLen) : $serviceName;
  }

  public static function createUtilClassName(string $namespace): string {
    return Naming::createClassName(Naming::coerceNamespace($namespace, 'CRM'), 'ExtensionUtil');
  }

  /**
   * Force the use of 'CRM_Foo_Bar' or Civi\Foo\Bar'.
   *
   * @param string $namespace
   *   Either 'CRM_Foobar' or 'Civi\Foobar'
   * @param string $layout
   *   Values may be:
   *     - 'auto': Respect the configured <namespace>
   *     - 'CRM': Force the use of 'CRM_Foobar'
   *     - 'Civi': Force the use of 'Civi\Foobar'
   * @return string
   *   Either 'CRM_Foobar' or 'Civi\Foobar'
   */
  public static function coerceNamespace(string $namespace, string $layout): string {
    if ($layout === 'CRM') {
      $namespace = preg_replace(';^Civi\\\;', 'CRM_', $namespace);
      $namespace = preg_replace(';^Civi/;', 'CRM/', $namespace);
    }
    elseif ($layout === 'Civi') {
      $namespace = preg_replace(';^CRM_;', 'Civi\\', $namespace);
      $namespace = preg_replace(';^CRM/;', 'Civi/', $namespace);
    }
    return $namespace;
  }

}
