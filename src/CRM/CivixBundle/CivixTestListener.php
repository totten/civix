<?php
namespace CRM\CivixBundle;

use PHPUnit\Framework\Test;

class CivixTestListener implements \PHPUnit\Framework\TestListener {
  use \PHPUnit\Framework\TestListenerDefaultImplementation;

  public function startTest(Test $test): void {
    \Civix::reset();
  }

}
