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
   * Parse a PHP script and visit all the function() declarations in the main script.
   *
   * @param string $code
   *   Fully formed PHP code
   * @param callable $filter
   *   This callback is executed against each function from the main-script.
   *
   *   function(?string &$functionName, string &$signature, string &$code): ?string
   *
   *   Note that inputs are all alterable. Additionally, the result may optionally specify
   *   an action to perform on the overall function ('DELETE', 'COMMENT').
   *
   *   If the main-script has an anonymous function (long-form: `$f = function (...) use (...) { ... }`),
   *   then it will be recognized with NULL name. However, short-form `fn()` is not.
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

    while (($peek = $this->peek(FALSE)) !== NULL) {
      if ($peek->is(T_USE)) {
        $statement = $this->fastForward(';');
        $output .= $statement;
      }
      elseif ($peek->is(T_FUNCTION)) {
        $output .= $this->parseFunction();
      }
      else {
        $output .= $this->consume()->value();
      }
    }
    return $output;
  }

  private function parseFunction(): string {
    $this->consume()->assert(T_FUNCTION);

    $pad0 = $this->fastForward([T_STRING, '(']);
    if ($this->peek()->is(T_STRING)) {
      $function = $this->consume()->value();
    }
    else {
      $function = NULL;
    }

    $pad1 = $this->fastForward('(');
    $signature = $this->parseSection('(', ')');

    $pad2 = $this->fastForward(['{', ';']);
    if ($this->peek()->is(';')) {
      // Abstract functions don't have bodies. For the moment, we don't care about visiting them.
      // but maybe that changes at some point...
      return 'function' . $pad0 . $function . $pad1 . '(' . $signature . ')' . $pad2 . $this->consume()->value();
    }
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
    elseif ($function == NULL) {
      return 'function' . $pad0 . $pad1 . '(' . $signature . ')' . $pad2 . '{' . $codeBlock . '}';
    }
    else {
      return 'function' . $pad0 . $function . $pad1 . '(' . $signature . ')' . $pad2 . '{' . $codeBlock . '}';
    }
  }

  private function parseSection(string $openChar, string $closeChar): string {
    $this->consume()->assert($openChar);
    $depth = 1;
    $section = '';

    while (($token = $this->consume()) !== NULL) {
      if ($token->is($closeChar)) {
        $depth--;
        if ($depth === 0) {
          break;
        }
      }
      $section .= $token->value();
      if ($token->is($openChar)) {
        $depth++;
      }
    }
    return $section;
  }

  private function consume(bool $required = TRUE): ?Token {
    if ($this->currentIndex < count($this->tokens)) {
      return $this->tokens[$this->currentIndex++];
    }
    if ($required) {
      throw new ParseException("Unexpected end of file. Cannot consume next token.");
    }
    return NULL;
  }

  private function peek(bool $required = TRUE): ?Token {
    if ($required && !isset($this->tokens[$this->currentIndex])) {
      throw new ParseException("Unexpected end of file. Cannot peek at next token.");
    }
    return $this->tokens[$this->currentIndex] ?? NULL;
  }

  private function fastForward($expectedToken): string {
    $output = '';
    while (($token = $this->peek(FALSE)) !== NULL) {
      if ($token->is($expectedToken)) {
        break;
      }
      else {
        $output .= $token->value();
      }
      $this->consume();
    }
    return $output;
  }

}
