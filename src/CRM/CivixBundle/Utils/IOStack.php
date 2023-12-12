<?php

namespace CRM\CivixBundle\Utils;

use Symfony\Component\Console\Style\SymfonyStyle;

class IOStack {

  protected $stack = [];

  public function push(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): void {
    array_unshift($this->stack, [
      'input' => $input,
      'output' => $output,
      'io' => new SymfonyStyle($input, $output),
    ]);
  }

  public function pop(): array {
    return array_shift($this->stack);
  }

  public function current(string $property) {
    return $this->stack[0][$property];
  }

  public function reset() {
    $this->stack = [];
  }

}
