<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Builder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A collection of builders
 */
class Collection implements Builder {
  public function __construct($builders = array()) {
    $this->builders = $builders;
  }

  public function loadInit(&$ctx) {
    foreach ($this->builders as $builder) {
      $builder->loadInit($ctx);
    }
  }

  public function init(&$ctx) {
    foreach ($this->builders as $builder) {
      $builder->init($ctx);
    }
  }

  public function load(&$ctx) {
    print_r($this);
    foreach ($this->builders as $builder) {
      $builder->load($ctx);
    }
  }

  public function save(&$ctx, OutputInterface $output) {
    foreach ($this->builders as $builder) {
      $builder->save($ctx, $output);
    }
  }

}
