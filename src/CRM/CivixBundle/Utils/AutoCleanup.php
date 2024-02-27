<?php

namespace CRM\CivixBundle\Utils;

/**
 * Define a local cleanup object (which will run on-destruct).
 *
 * Example:
 *
 * ```
 * $foo = allocateResource();
 * $cleanup = new AutoCleanup(function() use ($foo) {
 *   releaseResource($foo);
 * });
 * ```
 */
class AutoCleanup {

  protected $callback;

  /**
   * @param $callback
   */
  public function __construct($callback) {
    $this->callback = $callback;
  }

  public function __destruct() {
    call_user_func($this->callback);
  }

  /**
   * Prohibit (de)serialization of AutoCleanup.
   *
   * The generic nature of AutoClean makes it a potential target for escalating
   * serialization vulnerabilities, and there's no good reason for serializing it.
   */
  public function __sleep() {
    throw new \RuntimeException("AutoCleanup is a runtime helper. It is not intended for serialization.");
  }

  /**
   * Prohibit (de)serialization of AutoCleanup.
   *
   * The generic nature of AutoClean makes it a potential target for escalating
   * serialization vulnerabilities, and there's no good reason for deserializing it.
   */
  public function __wakeup() {
    throw new \RuntimeException("AutoCleanup is a runtime helper. It is not intended for deserialization.");
  }

}
