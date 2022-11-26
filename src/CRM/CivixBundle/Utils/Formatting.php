<?php

namespace CRM\CivixBundle\Utils;

class Formatting {

  /**
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
  public static function ol(string $itemFormat, iterable $list) {
    $buf = '';
    foreach ($list as $int => $item) {
      $item = (array) $item;
      $line = sprintf($itemFormat, ...$item);
      $buf .= ((1 + $int) . ". " . $line);
    }
    return rtrim($buf);
  }

}
