<?php

namespace CRM\CivixBundle\Parse;

if (!defined('TX_RAW')) {
  define('TX_RAW', -1);
}

/**
 * OOP wrapper of PHP's token.
 *
 * For writing recursive-descent parsers, it can get a little tedious
 * to constantly guard against `is_array()/is_string()`.
 */
class Token {

  public static function tokenize(string $code): array {
    $raw = token_get_all($code);
    return array_map(function ($t) {
      return is_array($t) ? new Token($t[0], $t[1]) : new Token(TX_RAW, $t);
    }, $raw);
  }

  /**
   * @param $typeId
   * @param $value
   */
  public function __construct(int $typeId, string $value) {
    $this->typeId = $typeId;
    $this->value = $value;
  }

  protected $typeId;
  protected $value;

  /**
   * Get ID code of the token type.
   *
   * @return int
   *   Ex: T_FUNCTION, T_WHITESPACE, TX_RAW
   */
  public function typeId(): int {
    return $this->typeId;
  }

  /**
   * Get symbolic name of the token type.
   *
   * @return string
   *   Ex: 'T_FUNCTION', 'T_WHITESPACE_', 'TX_RAW'
   */
  public function name(): string {
    if ($this->typeId === TX_RAW) {
      return 'TX_RAW';
    }
    else {
      return token_name($this->typeId);
    }
  }

  /**
   * Get literal value of token.
   *
   * @return string
   */
  public function value(): string {
    return $this->value;
  }

  /**
   * Check if the token matches an expectation
   *
   * @param $expect
   *   One of:
   *    - Integer: The token-type ID
   *    - String: The raw value of the token
   *    - Array: List of strings or integers; any one to match
   * @return bool
   */
  public function is($expect): bool {
    if ($expect === NULL) {
      return TRUE; /* wildcard */
    }
    if (is_array($expect)) {
      foreach ($expect as $expectOption) {
        if ($this->is($expectOption)) {
          return TRUE;
        }
      }
      return FALSE;
    }
    if (is_int($expect)) {
      return $this->typeId === $expect;
    }
    if (is_string($expect)) {
      return $this->value === $expect;
    }
    throw new ParseException("Token::is() expects type ID or literal value");
  }

  public function assert($expect): void {
    if ($this->is($expect)) {
      return;
    }

    if ($expect === TX_RAW) {
      $expectPretty = 'TX_RAW';
    }
    elseif (is_int($expect)) {
      $expectPretty = token_name($expect);
    }
    else {
      $expectPretty = json_encode($expect, JSON_UNESCAPED_SLASHES);
    }

    throw new ParseException(sprintf('Token %s does not match expectation %s', $this->__toString(), $expectPretty));
  }

  public function __toString(): string {
    return sprintf("%s [%s] %s", $this->name(), $this->typeId, json_encode($this->value(), JSON_UNESCAPED_SLASHES));
  }

}
