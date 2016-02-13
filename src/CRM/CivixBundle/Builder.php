<?php
namespace CRM\CivixBundle;

use Symfony\Component\Console\Output\OutputInterface;

interface Builder {
  public function loadInit(&$ctx);

  public function init(&$ctx);

  public function load(&$ctx);

  public function save(&$ctx, OutputInterface $output);

}
