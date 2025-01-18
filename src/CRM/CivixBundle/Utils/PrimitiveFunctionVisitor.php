<?php

namespace CRM\CivixBundle\Utils;

/**
 * Given a PHP file, visit all functions in the file. Use this for primitive
 * checks ("does the function do anything") or primitive manipulations
 * ("add a new line, or rename the whole thing").
 *
 * ## Example 1: Print out a list functions
 *
 * PrimitiveFunctionVisitor::visit(file_get_contents('foo.php', function(string $function, string $signature, string $code) {
 *   printf("FOUND: function %s(%s) {...}\n", $function, $signature);
 * });
 *
 * ## Example 2: Rename a function from 'foo' to 'bar'
 *
 * $updated = PrimitiveFunctionVisitor::visit(file_get_contents('foo.php', function(string &$function, string &$signature, string &$code) {
 *   if ($function === 'foo') {
 *     $function = 'bar';
 *   }
 * });
 *
 * ## Example 3: Delete any functions with evil names
 *
 * $updated= PrimitiveFunctionVisitor::visit(file_get_contents('foo.php', function(string $function, string $signature, string $code) {
 *    return (str_contains($function, 'evil')') ? 'DELETE' : NULL;
 *  });
 */
class PrimitiveFunctionVisitor {
  private $tokens;
  private $filter;
  private $currentIndex = 0;

  /**
   * Parse a PHP script and visit all the function(){} declarations.
   *
   * @param string $code
   *   Fully formed PHP code
   * @param callable $filter
   *   This callback is executed against each function.
   *
   *   function(string &$functionName, string &$signature, string &$code): ?string
   *
   *   Note that inputs are all alterable. Additionally, the result may optionally specify
   *   an action to perform on the overall function ('DELETE', 'COMMENT').
   *
   * @return string
   */
  public static function visit(string $code, callable $filter): string {
    $instance = new self($code, $filter);
    return $instance->run();
  }

  public function __construct(string $code, callable $filter) {
    $this->tokens = token_get_all($code);
    $this->filter = $filter;
  }

  public function run(): string {
    $output = '';
    while (($token = $this->nextToken()) !== NULL) {
      if ($this->isToken($token, T_FUNCTION)) {
        $output .= $this->parseFunction();
      }
      else {
        $output .= is_array($token) ? $token[1] : $token;
      }
    }
    return $output;
  }

  private function parseFunction(): string {
    $pad0 = $this->fastForward(T_STRING);
    $function = $this->nextToken()[1];

    $pad1 = $this->fastForward('(');
    $signature = $this->parseSection('(', ')');

    $pad2 = $this->fastForward('{');
    $codeBlock = $this->parseSection('{', '}');

    $result = ($this->filter)($function, $signature, $codeBlock);

    if ($result === 'DELETE') {
      return '';
    }
    elseif ($result === 'COMMENT') {
      $code = 'function' . $pad0 . $function . $pad1 . '(' . $signature . ')' . $pad2 . '{' . $codeBlock . '}';
      return "\n" . implode("\n", array_map(
          function($line) {
            return "// $line";
          },
          explode("\n", $code)
        )) . "\n";
    }
    else {
      return 'function' . $pad0 . $function . $pad1 . '(' . $signature . ')' . $pad2 . '{' . $codeBlock . '}';
    }
  }

  private function parseSection(string $openChar, string $closeChar): string {
    $this->assertToken($this->peekToken(), $openChar);
    $section = '';
    $parenthesisCount = 0;

    while (($token = $this->nextToken()) !== NULL) {
      $section .= is_array($token) ? $token[1] : $token;

      if ($token === $openChar) {
        $parenthesisCount++;
      }
      elseif ($token === $closeChar) {
        $parenthesisCount--;
        if ($parenthesisCount === 0) {
          break;
        }
      }
    }
    return substr($section, 1, -1);
  }

  private function isToken($token, $type): bool {
    if (is_array($token)) {
      return $token[0] === $type;
    }
    else {
      return $token === $type;
    }
  }

  private function assertToken($token, $type): void {
    if ($type === NULL) {
      return;
    }

    if ($this->isToken($token, $type)) {
      return;
    }

    $actualTypeName = is_array($token) ? token_name($token[0]) : $token;
    $expectTypeName = is_string($type) ? $token : token_name($type);
    throw new \RuntimeException(sprintf('Token %s does not match type %s', json_encode($actualTypeName), json_encode($expectTypeName)));
  }

  private function nextToken(?string $assertType = NULL) {
    if ($this->currentIndex < count($this->tokens)) {
      $token = $this->tokens[$this->currentIndex++];
      $this->assertToken($token, $assertType);
      return $token;
    }
    return NULL;
  }

  private function peekToken() {
    return $this->tokens[$this->currentIndex] ?? NULL;
  }

  private function fastForward($expectedToken): string {
    $output = '';
    while (($token = $this->peekToken()) !== NULL) {
      if (is_array($token)) {
        if ($token[0] === $expectedToken) {
          break;
        }
        else {
          $output .= $token[1];
        }
      }
      else {
        if ($token === $expectedToken) {
          break;
        }
        else {
          $output .= $token;
        }
      }
      $this->nextToken();
    }
    return $output;
  }

}
