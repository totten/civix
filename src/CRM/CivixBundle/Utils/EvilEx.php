<?php

namespace CRM\CivixBundle\Utils;

/**
 * Evil Expressions: What happens when you don't import nikic/php-parser.
 */
class EvilEx {

  /**
   * Find a function-body, and run it through a filter.
   *
   * Limitation: This only matches top-level functions, where the `function x()` begins in the leftmost,
   * and where the closing `}` is als in the leftmost, and where the body is indented.
   *
   * @param string $body
   *   Full-text file.
   * @param string $function
   *   Name of the function to filter.
   * @param callable $filter
   *   Filter the function-body; return new body.
   *   function(string $body): string
   * @return string
   *   Updated body.
   */
  public static function rewriteFunction(string $body, string $function, callable $filter): string {
    $pattern = "/(\nfunction " . $function . "\([^{]+\)\s*{\n)((\n|  [^\n]*\n)*)(}\n)/m";
    return preg_replace_callback($pattern,
      function ($m) use ($filter) {
        $body = $m[2];
        $newBody = $filter($body);
        return $m[1] . $newBody . $m[4];
      },
      $body
    );
  }

  /**
   * Searches for a chunk of code - and replaces it.
   *
   * When matching the chunk of code, it specifically ignores whitespace and blank lines.
   *
   * @param string $body
   * @param array $matchChunk
   *   A series of lines which should be found somewhere inside $body (modulo whitespace).
   * @param callable $filterChunk
   *   Filter the matching lines; return new lines.
   *   function(array $matchLines): array
   * @return string
   *   Updated body.
   */
  public static function rewriteMultilineChunk(string $body, array $matchChunk, callable $filterChunk) {
    $expectLines = EvilEx::digestLines($matchChunk);
    $actualLines = EvilEx::digestLines(explode("\n", $body));
    foreach (array_keys($actualLines) as $startOffset) {
      $endOffset = NULL;
      $expectLineNum = 0;
      for ($actualLineNum = $startOffset; $actualLineNum < count($actualLines); $actualLineNum++) {
        if (empty($actualLines[$actualLineNum]['dig'])) {
          continue;
        }
        if ($expectLines[$expectLineNum]['dig'] !== $actualLines[$actualLineNum]['dig']) {
          continue 2;
        }
        $expectLineNum++;
        if ($expectLineNum >= count($expectLines)) {
          $endOffset = $actualLineNum;
          break 2;
        }
      }
    }

    if ($endOffset === NULL) {
      return $body;
    }

    $rawActualLines = array_column($actualLines, 'raw');
    $matchLines = array_slice($rawActualLines, $startOffset, $endOffset - $startOffset + 1, TRUE);
    $newLines = $filterChunk($matchLines);
    array_splice($rawActualLines, $startOffset, $endOffset - $startOffset + 1, $newLines);
    return implode("\n", $rawActualLines);
  }

  public static function digestLine(string $line): string {
    return mb_strtolower(preg_replace('/\s+/', '', $line));
  }

  public static function digestLines($lines): array {
    $result = [];
    foreach ($lines as $line) {
      $result[] = ['raw' => $line, 'dig' => static::digestLine($line)];
    }
    return $result;
  }

}
