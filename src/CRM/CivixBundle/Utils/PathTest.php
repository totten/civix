<?php

namespace CRM\CivixBundle\Utils;

/**
 * @group unit
 */
class PathTest extends \PHPUnit\Framework\TestCase {

  public function testPathFor() {
    $this->assertEquals('/var/www', Path::for('/var/www'));
    $this->assertEquals('e:/project/web', Path::for('e:\\project\\web'));
    $this->assertEquals('e:/project/web/ab/cd/ef', Path::for('e:\\project\\web', 'ab', 'cd\\ef'));
    $this->assertEquals('/var/www/foo/bar', Path::for('/var/www', 'foo/bar'));
    $this->assertEquals('/var/www/foo/bar', Path::for('/var/www', 'foo\bar'));
    $this->assertEquals('/var/www/foobar/whizbang', Path::for('/var/www', 'foobar', 'whizbang'));
  }

  public function testPathString() {
    $base = Path::for('/var/www');
    $this->assertEquals('/var/www', $base->string());
    $this->assertEquals('/var/www/foo', $base->string('foo'));
    $this->assertEquals('/var/www/foo/bar', $base->string('foo', 'bar'));
    $this->assertEquals('/var/www/foo/bar', $base->string('foo/bar'));
    $this->assertEquals('/var/www/foo/bar', $base->string('foo\\bar'));
  }

  public function testPathChild() {
    $this->assertEquals('/var/www/foo', (string) Path::for('/var')->path('www')->path('foo'));
    $this->assertEquals('/var/www/foo/whiz/bang', (string) Path::for('/var')->path('www', 'foo')->path('whiz/bang'));
    $this->assertEquals('e:/work/www/foo/whiz/bang', (string) Path::for('e:\\work')->path('www', 'foo')->path('whiz\\bang'));
  }

}
