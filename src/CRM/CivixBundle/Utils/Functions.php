<?php

namespace CRM\CivixBundle\Utils;

class Functions {

  /**
   * Create a "curried function" in which some arguments are pre-applied.
   *
   * Ex:
   *   $sha256 = Functions::curry('hash', 'sha256');
   *   echo $sha256('hello world');
   *   // Equivalent to `hash('sha256', 'hello world')`
   *
   * @param callable $callable
   * @param mixed ...$args1
   * @return \Closure
   */
  public static function curry($callable, ...$args1) {
    return function (...$args2) use ($callable, $args1) {
      $allArgs = array_merge($args1, $args2);
      return $callable(...$allArgs);
    };
  }

}
