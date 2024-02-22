<?php

namespace CRM\CivixBundle\Utils;

class Formatting {

  /**
   * Format an ordered list.
   *
   * @param string $itemFormat
   *   Ex: "Hello %s.\n"
   *   Ex: "Hello %s from %s.\n"
   *   Ex: "Everyone in %2$s says hello to %1$s."
   * @param iterable $list
   *   Ex: ['Alice', 'Bob']
   *   Ex: [['Alice','Argentina'], ['Bob','Britain']]
   * @return string
   *   Ex: "1. Hello Alice.\n2. Hello Bob.\n"
   *   Ex: "1. Hello Alice from Argentina!\n2. Hello Bob from Britain!"
   */
  public static function ol(string $itemFormat, iterable $list) {
    $buf = '';
    foreach ($list as $int => $item) {
      $item = (array) $item;
      $line = sprintf($itemFormat, ...$item);
      $buf .= ((1 + $int) . ". " . $line);
    }
    return rtrim($buf);
  }

  /**
   * Format an unordered list.
   *
   * @param string $itemFormat
   *   Ex: "Hello %s.\n"
   *   Ex: "Hello %s from %s.\n"
   *   Ex: "Everyone in %2$s says hello to %1$s."
   * @param iterable $list
   *   Ex: ['Alice', 'Bob']
   *   Ex: [['Alice','Argentina'], ['Bob','Britain']]
   * @return string
   *   Ex: "Hello Alice.\nHello Bob.\n"
   *   Ex: "Hello alice from Argentina!\nHello Bob from Britain!"
   */
  public static function ul(string $itemFormat, iterable $list) {
    $buf = '';
    foreach ($list as $int => $item) {
      $item = (array) $item;
      $line = sprintf($itemFormat, ...$item);
      $buf .= "- " . $line;
    }
    return rtrim($buf);
  }

}
