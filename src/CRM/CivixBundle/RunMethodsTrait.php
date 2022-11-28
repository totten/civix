<?php

namespace CRM\CivixBundle;

/**
 * Use this trait if you want to delegate to an open-ended list of methods.
 *
 * For example, suppose you want a method "checkRequirements()" which is built on running
 * methods "checkRequirements_foo()","checkRequirements_bar()", etc.
 *
 * You could implement as:
 *
 *   use RunMethodsTrait;
 *   public function checkRequirements() {
 *     $this->runMethods('/^checkRequirements_/');
 *   }
 *   protected function checkRequirements_foo() { ... }
 *   protected function checkRequirements_bar() { ... }
 */
trait RunMethodsTrait {

  /**
   *
   * Ex: [$results] = $this->runMethods(';foo.*;');
   * Ex: [$results, $skipped] = $this->runMethods(';foo.*;', ['val1', 'val2']);
   *
   * @param string $methodRegex
   * @param array $params
   *   A list of parameters to pass down to each method.
   * @return array
   *   Results of calling the methods.
   *   Some methods have opted-out of execution.
   *   Formally, the results are a tuple of `[$executed, $skipped]`.
   * @throws \ReflectionException
   */
  protected function runMethods(string $methodRegex, array $params = []): array {
    $executed = [];
    $skipped = [];

    $class = new \ReflectionClass($this);
    foreach ($class->getMethods() as $method) {
      /** @var \ReflectionMethod $method */

      if (preg_match($methodRegex, $method->getName())) {
        try {
          $result = $method->invoke($this, ...$params);
          $executed[$method->getName()] = $result;
        }
        catch (SkippedMethodException $e) {
          $skipped[$method->getName()] = $method->getName();
        }
      }
    }

    return [$executed, $skipped];
  }

  /**
   * Assert that the current method is (or is not) applicable.
   *
   * @param bool $bool
   *   If TRUE, then proceed with normal execution.
   *
   *   If FALSE, raise an exception that will propagate back to the main `testSnapshot()` method.
   *   The next check will run.
   */
  protected function runsIf(bool $bool) {
    if (!$bool) {
      throw new SkippedMethodException();
    }
  }

}
