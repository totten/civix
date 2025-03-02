<?php

namespace CRM\CivixBundle\Parse;

/**
 * @group unit
 */
class PrimitiveFunctionVisitorTest extends \PHPUnit\Framework\TestCase {

  protected function getBasicFile(): string {
    $code = '<' . '?php ';
    $code .= "// Woop\n";
    $code .= "// Woop\n";
    $code .= "function first() { echo 1; }\n";
    $code .= "/**\n * Woop\n */\n";
    $code .= "   function second(array ?\$xs = []) { echo 2; }\n";
    // TODO Nested block
    $code .= "if (foo( 'bar' )) { function third(\$a, \$b, \$c) { echo 3; } }\n";
    return $code;
  }

  protected function getComplexFile(): string {
    $code = '<' . '?php ';
    $code .= "// Complex\n";
    $code .= '$anon = function(int $a) { return 100; };';
    $code .= '$bufA = [];';
    $code .= '(new Stuff())->do(wrapping(200, function($x, $y) use ($buf) {';
    $code .= '  if ($y) {';
    $code .= '    if (time()) {';
    $code .= '      return $x;';
    $code .= '    }';
    $code .= '    else {}';
    $code .= '  }';
    $code .= '  else { womp(); womp(); }';
    $code .= '}));';
    $code .= 'function zero($a) { /* nullop */ };';
    $code .= '?>';
    $code .= $this->getBasicFile();
    $code .= 'function finale($z) {}';
    return $code;
  }

  /**
   * Handy for interactive debugging...
   */
  public function testTiny(): void {
    $code = '<' . '?php ';
    $code .= "function single(\$a) { return 'bar'; }";
    PrimitiveFunctionVisitor::visit($code, function (&$func, &$sig, &$code) use (&$visited) {
      $visited[] = ['func' => $func, 'sig' => $sig, 'code' => $code];
    });
    $expected = [];
    $expected[] = ['func' => 'single', 'sig' => '$a', 'code' => ' return \'bar\'; '];
    $this->assertEquals($expected, $visited);
  }

  public function testBasicFileVisitOrder(): void {
    $input = $this->getBasicFile();

    $visited = [];
    $output = PrimitiveFunctionVisitor::visit($input, function (&$func, &$sig, &$code) use (&$visited) {
      $visited[] = ['func' => $func, 'sig' => $sig, 'code' => $code];
    });
    $expected = [];
    $expected[] = ['func' => 'first', 'sig' => '', 'code' => ' echo 1; '];
    $expected[] = ['func' => 'second', 'sig' => 'array ?$xs = []', 'code' => ' echo 2; '];
    $expected[] = ['func' => 'third', 'sig' => '$a, $b, $c', 'code' => ' echo 3; '];
    $this->assertEquals($expected, $visited);

    $this->assertEquals($input, $output);
  }

  public function testBasicFileInsertions(): void {
    $input = $this->getBasicFile();

    $output = PrimitiveFunctionVisitor::visit($input, function (&$func, &$sig, &$code) {
      $func .= 'Func';
      if (!empty($sig)) {
        $sig .= ', ';
      }
      $sig .= 'int $id = 0';
      $code .= 'echo "00"; ';
    });

    $expect = '<' . '?php ';
    $expect .= "// Woop\n";
    $expect .= "// Woop\n";
    $expect .= "function firstFunc(int \$id = 0) { echo 1; echo \"00\"; }\n";
    $expect .= "/**\n * Woop\n */\n";
    $expect .= "   function secondFunc(array ?\$xs = [], int \$id = 0) { echo 2; echo \"00\"; }\n";
    $expect .= "if (foo( 'bar' )) { function thirdFunc(\$a, \$b, \$c, int \$id = 0) { echo 3; echo \"00\"; } }\n";
    $this->assertEquals($expect, $output);
  }

  public function testBasicFileDeletion(): void {
    $input = $this->getBasicFile();

    $output = PrimitiveFunctionVisitor::visit($input, function (&$func, &$sig, &$code) {
      return ($func === 'second') ? 'DELETE' : NULL;
    });

    $expect = '<' . '?php ';
    $expect .= "// Woop\n";
    $expect .= "// Woop\n";
    $expect .= "function first() { echo 1; }\n";
    $expect .= "/**\n * Woop\n */\n";
    $expect .= "   \n";
    $expect .= "if (foo( 'bar' )) { function third(\$a, \$b, \$c) { echo 3; } }\n";
    $this->assertEquals($expect, $output);
  }

  public function testBasicFileComment(): void {
    $input = $this->getBasicFile();

    $output = PrimitiveFunctionVisitor::visit($input, function (&$func, &$sig, &$code) {
      return ($func === 'third') ? 'COMMENT' : NULL;
    });

    $expect = '<' . '?php ';
    $expect .= "// Woop\n";
    $expect .= "// Woop\n";
    $expect .= "function first() { echo 1; }\n";
    $expect .= "/**\n * Woop\n */\n";
    $expect .= "   function second(array ?\$xs = []) { echo 2; }\n";
    $expect .= "if (foo( 'bar' )) { \n";
    $expect .= "// function third(\$a, \$b, \$c) { echo 3; }\n";
    $expect .= " }\n";
    $this->assertEquals($expect, $output);
  }

  public function testMinSpacing(): void {
    $input = '<' . '?php ';
    $input .= "function first(){echo 1;}";
    $input .= "if(foo()){function second(){echo 3;}}";

    $visited = [];
    $output = PrimitiveFunctionVisitor::visit($input, function (&$func, &$sig, &$code) use (&$visited) {
      $visited[] = $func;
    });
    $this->assertEquals(['first', 'second'], $visited);
    $this->assertEquals($input, $output);
  }

  public function testInterface(): void {
    $input = '<' . '?php ';
    $input .= "interface Food {";
    $input .= "  public function apple();\n";
    $input .= "  public function banana();\n";
    $input .= "}\n";

    // For the moment, we don't really care about visiting abstract functions. But maybe that changes sometime.
    $visited = [];
    $output = PrimitiveFunctionVisitor::visit($input, function (&$func, &$sig, &$code) use (&$visited) {
      $visited[] = $func;
    });
    $this->assertEquals([], $visited);
    $this->assertEquals($input, $output);
  }

  public function testUseFunction(): void {
    $input = '<' . '?php ';
    $input .= 'namespace foo;';
    $input .= 'use function bar;';
    $input .= 'class Whiz { function bang() {} }';

    // For the moment, we don't really care about visiting abstract functions. But maybe that changes sometime.
    $visited = [];
    $output = PrimitiveFunctionVisitor::visit($input, function (&$func, &$sig, &$code) use (&$visited) {
      $visited[] = $func;
    });
    $this->assertEquals(['bang'], $visited);
    $this->assertEquals($input, $output);
  }

  public function testComplexFileVisitOrder(): void {
    $input = $this->getComplexFile();

    $visited = [];
    $output = PrimitiveFunctionVisitor::visit($input, function (&$func, &$sig, &$code) use (&$visited) {
      $visited[] = ['func' => $func, 'sig' => $sig, 'code' => $code];
    });
    $expected = [];
    $expected[] = ['func' => NULL, 'sig' => 'int $a', 'code' => ' return 100; '];
    $expected[] = ['func' => NULL, 'sig' => '$x, $y', 'code' => '  if ($y) {    if (time()) {      return $x;    }    else {}  }  else { womp(); womp(); }'];
    $expected[] = ['func' => 'zero', 'sig' => '$a', 'code' => ' /* nullop */ '];
    $expected[] = ['func' => 'first', 'sig' => '', 'code' => ' echo 1; '];
    $expected[] = ['func' => 'second', 'sig' => 'array ?$xs = []', 'code' => ' echo 2; '];
    $expected[] = ['func' => 'third', 'sig' => '$a, $b, $c', 'code' => ' echo 3; '];
    $expected[] = ['func' => 'finale', 'sig' => '$z', 'code' => ''];
    $this->assertEquals($expected, $visited);

    $this->assertEquals($input, $output);
  }

}
