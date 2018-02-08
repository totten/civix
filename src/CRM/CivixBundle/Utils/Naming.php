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
    return
      preg_match('/^[a-z][a-z0-9\.\-]*$/', $fullName)
      && strpos($fullName, '..') === FALSE
      && !preg_match('/\.$/', $fullName);
  }

  /**
   * Based on a full name, determine a short name.
   *
   * @param string $fullName
   *   Ex: 'org.example.foo'
   *   Ex: 'civicrm-foo-bar'
   * @return string
   *   Ex: 'foo'
   *   Ex: 'foo_bar'
   */
  public static function createShortName($fullName) {
    // If the extension name has a . in it, only use the end of the extension
    // name for the short name
    $nameParts = explode('.', $fullName);
    $shortName = end($nameParts);

    // If the short name starts with civicrm-, strip it
    if (strpos($shortName, 'civicrm-') === 0) {
      $shortName = substr($shortName, 8);
    }
    $shortName = str_replace('-', '_', $shortName);

    return $shortName;
  }

  /**
   * Based on a full name, determine a camel case name.
   *
   * @param string $fullName
   *   Ex: 'org.example.foo'
   *   Ex: 'civicrm-foo-bar'
   * @return string
   *   Ex: 'Foo'
   *   Ex: 'FooBar'
   */
  public static function createCamelName($fullName) {
    $shortName = self::createShortName($fullName);
    $camelCase = '';
    foreach (explode('_', $shortName) as $shortNamePart) {
      $camelCase .= ucfirst($shortNamePart);
    }
    return $camelCase;
  }

}
