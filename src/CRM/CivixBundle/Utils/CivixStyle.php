<?php

namespace CRM\CivixBundle\Utils;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CivixStyle extends SymfonyStyle {

  private $input;
  private $output;

  public function __construct(InputInterface $input, OutputInterface $output) {
    $this->input = $input;
    $this->output = $output;
    parent::__construct($input, $output);
  }

  public function confirm(string $question, bool $default = TRUE) {
    if ($this->input->hasOption('yes') && $this->input->getOption('yes') && $default) {
      return TRUE;
    }
    return parent::confirm($question, $default);
  }

  public function choice(string $question, array $choices, $default = NULL) {
    if ($this->input->hasOption('yes') && $this->input->getOption('yes') && $default !== NULL) {
      return $default;
    }

    return parent::choice($question, $choices, $default);
  }

}
