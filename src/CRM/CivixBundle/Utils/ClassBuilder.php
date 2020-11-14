<?php

namespace CRM\CivixBundle\Utils;

/**
 * Class ClassBuilder
 *
 * This utility helps to build a new class by copying on an existing class. It
 * handles a few bits of the transformation:
 *
 * - Locate/read the original class code.
 * - Search/replace the name of the class.
 * - Optionally, add 'namespace' statement.
 * - Optionally, add 'use XXX' statements.
 * - Optionally, add '@FOO@' variables.
 * - Optionally, locate and inline traits (if flagged with '// civix:inline_trait').
 *
 * @package CRM\CivixBundle\Utils
 */
class ClassBuilder {

  protected $outClass;

  protected $inClass;

  protected $comments = [];

  protected $uses = [];

  protected $vars = [];

  /**
   * @param string $outClass
   *
   * @return static
   */
  public static function create($outClass) {
    $cb = new static();
    $cb->outClass = $outClass;
    return $cb;
  }

  /**
   * @return mixed
   */
  public function getOutClass() {
    return $this->outClass;
  }

  /**
   * @param mixed $outClass
   *
   * @return $this
   */
  public function setOutClass($outClass) {
    $this->outClass = $outClass;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getInClass() {
    return $this->inClass;
  }

  /**
   * @param mixed $inClass
   *
   * @return $this
   */
  public function setInClass($inClass) {
    $this->inClass = $inClass;
    return $this;
  }

  /**
   * @return null
   */
  public function getComments() {
    return $this->comments;
  }

  /**
   * @param string|string[] $comments
   *
   * @return $this
   */
  public function addComments($comments) {
    $comments = (array) $comments;
    $this->comments = array_merge($this->comments, $comments);
    return $this;
  }

  /**
   * @return array
   */
  public function getUses(): array {
    return $this->uses;
  }

  /**
   * @param string|string[] $uses
   * @return $this;
   */
  public function addUses($uses) {
    $uses = (array) $uses;
    $this->uses = array_unique(array_merge($this->uses, $uses));
    sort($this->uses);
    return $this;
  }

  /**
   * Add variables which will be replaced in the final code.
   *
   * @param array $vars
   *   Ex: ['@FOO@' => 'fooBar']
   *
   * @return $this
   */
  public function addVars($vars) {
    $this->vars = array_merge($this->vars, $vars);
    return $this;
  }

  /**
   * Generate PHP code for the class.
   *
   * @return string
   */
  public function toPHP() {
    $code = $this->readClass($this->inClass);

    if (strpos($this->outClass, '\\') === FALSE) {
      $code = str_replace($this->inClass, $this->outClass, $code);
      $ns = NULL;
    }
    else {
      $outClassParts = explode('\\', $this->outClass);
      $outClassEff = array_pop($outClassParts);
      $ns = implode('\\', $outClassParts);
      $code = str_replace($this->inClass, $outClassEff, $code);
    }

    $inlineTrait = function($m) {
      return $this->readTrait($m[1]);
    };
    $code = preg_replace_callback(';\n *//\s*civix:inline_trait\s*use +([A-Za-z0-9_\\\]+)\\;;', $inlineTrait, $code);

    $evalVar = function($m) {
      $var = $m[1];
      return isset($this->vars[$var]) ? $this->vars[$var] : "@" . $var . "@";
    };
    $code = preg_replace_callback(';(@([a-zA-Z0-9_\.]+)@);', $evalVar, $code);

    $finalParts = [];

    if ($ns) {
      $finalParts[] = "namespace $ns;";
      $finalParts[] = '';
    }

    if ($this->uses) {
      $finalParts = array_merge($finalParts, $this->uses);
      $finalParts[] = '';
    }

    if ($this->comments) {
      $finalParts[] = '/' . '**';
      foreach ($this->comments as $comment) {
        $finalParts[] = rtrim(' * ' . $comment);
      }
      $finalParts[] = ' *' . '/';
    }

    $finalParts[] = $code;

    return implode("\n", $finalParts);
  }

  private function readClass($inClass) {
    $c = new \ReflectionClass($inClass);
    $code = file_get_contents($c->getFileName());
    $lines = explode("\n", $code);
    $code = implode("\n", array_slice($lines,
          $c->getStartLine() - 1,
          $c->getEndLine() - $c->getStartLine() + 1)
      ) . "\n";
    return $code;
  }

  private function readTrait($trait) {
    $c = new \ReflectionClass($trait);
    $code = trim(file_get_contents($c->getFileName()));
    $lines = explode("\n", $code);
    $code = implode("\n", array_slice($lines,
          $c->getStartLine(),
          $c->getEndLine() - $c->getStartLine() - 1)
      );
    return rtrim($code);

  }

}
