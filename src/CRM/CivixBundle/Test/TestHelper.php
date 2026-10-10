<?php

namespace CRM\CivixBundle\Test;

use ProcessHelper\ProcessHelper as PH;
use Symfony\Component\Process\Process;

class TestHelper {

  /**
   * Similar to ProcessHelper::runOk, but we specifically disable any subordinate xdebug.
   *
   * @param string|array|process $command
   *   Ex: 'cv en foobar'
   *   Ex: ['cv en @1 -v', 'foobar']
   *   Ex: ['cv en @KEY -v', 'KEY' => 'foobar']
   * @return \Symfony\Component\Process\Process
   */
  public static function runOk($command): Process {
    $p = PH::castToProcess($command);
    $p->setEnv(['XDEBUG_MODE' => 'off']);
    return PH::runOk($p);
  }

}
