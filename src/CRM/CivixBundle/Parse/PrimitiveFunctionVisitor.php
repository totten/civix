<?php

namespace CRM\CivixBundle\Parse;

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

  /**
   * @var Token[]
   */
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
    $this->tokens = Token::tokenize($code);
    $this->filter = $filter;
  }

  public function run(): string {
    $output = '';
    while (($token = $this->nextToken()) !== NULL) {
      if ($token->is(T_FUNCTION)) {
        $output .= $this->parseFunction();
      }
      else {
        $output .= $token->value();
      }
    }
    return $output;
  }

  private function parseFunction(): string {
    $pad0 = $this->fastForward(T_STRING);
    $function = $this->nextToken()->value();

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
    if ($this->peekToken() === NULL) {
      echo '';
    }
    $this->peekToken()->assert($openChar);
    $section = '';
    $depth = 0;

    while (($token = $this->nextToken()) !== NULL) {
      $section .= $token->value();

      if ($token->is($openChar)) {
        $depth++;
      }
      elseif ($token->is($closeChar)) {
        $depth--;
        if ($depth === 0) {
          break;
        }
      }
    }
    return substr($section, 1, -1);
  }

  private function nextToken(?string $assertType = NULL): ?Token {
    if ($this->currentIndex < count($this->tokens)) {
      $token = $this->tokens[$this->currentIndex++];
      $token->assert($assertType);
      return $token;
    }
    return NULL;
  }

  private function peekToken(): ?Token {
    return $this->tokens[$this->currentIndex] ?? NULL;
  }

  private function fastForward($expectedToken): string {
    $output = '';
    while (($token = $this->peekToken()) !== NULL) {
      if ($token->is($expectedToken)) {
        break;
      }
      else {
        $output .= $token->value();
      }
      $this->nextToken();
    }
    return $output;
  }

}
