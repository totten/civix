<?php

namespace CRM\CivixBundle\Command;

use Civix;
use CRM\CivixBundle\Parse\PrimitiveFunctionVisitor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InspectFunctionCommand extends AbstractCommand {

  protected function configure() {
    parent::configure();
    $this
      ->setName('inspect:fun')
      ->setDescription('Search codebase for functions')
      ->addOption('name', NULL, InputOption::VALUE_REQUIRED, 'Pattern describing the function-names you wnt to see')
      ->addOption('code', NULL, InputOption::VALUE_REQUIRED, 'Pattern describing function bodies that you want to see')
      ->addOption('files-with-matches', 'l', InputOption::VALUE_NONE, 'Print only file names')
      ->addArgument('files', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'List of files')
      ->setHelp('Search PHP functions

Example: Find all functions named like "_civicrm_permission"
  civix inspect:fun --name=/_civicrm_permission/ *.php

Example: Find all functions which call civicrm_api3()
  civix inspect:fun --code=/civicrm_api3/ *.php

Example: Find all functions matching the name and code patterns
  civix inspect:fun --name=/_civicrm_permission/ --code=/label/ *.php
');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $functionNamePattern = $input->getOption('name');
    $codePattern = $input->getOption('code');
    $printer = [$this, 'printMatch'];
    if ($input->getOption('files-with-matches')) {
      $printer = [$this, 'printFileName'];
    }

    foreach ($input->getArgument('files') as $file) {
      if ($output->isVeryVerbose()) {
        $output->writeln("## SCAN FILE: $file");
      }

      $code = file_get_contents($file);
      PrimitiveFunctionVisitor::visit($code, function (?string &$functionName, string &$signature, string &$code) use ($codePattern, $functionNamePattern, $file, $input, $printer) {
        if ($functionNamePattern && !preg_match($functionNamePattern, $functionName)) {
          return;
        }
        if ($codePattern && !preg_match($codePattern, $code)) {
          return;
        }

        $printer($file, $functionName, $signature, $code, $codePattern);
      });
    }

    return 0;
  }

  protected function printFileName($file, ?string $functionName, string $signature, string $code, $codePattern) {
    Civix::output()->writeln($file, OutputInterface::OUTPUT_RAW);
  }

  protected function printMatch($file, ?string $functionName, string $signature, string $code, $codePattern): void {
    Civix::output()->writeln(sprintf("## FILE: %s", $file));
    Civix::output()->write(sprintf("<comment>function <info>%s</info>(%s)</comment> {", $functionName, $signature));
    if (!$codePattern) {
      Civix::output()->write($code, FALSE, OutputInterface::OUTPUT_RAW);
    }
    else {
      $hiParts = $this->splitHighlights($code, $codePattern);
      foreach ($hiParts as $part) {
        Civix::output()->write($part[0], FALSE, $part[1]);
      }
    }
    Civix::output()->write("}\n\n");
  }

  /**
   * @param string $code
   * @param $hi
   * @param $matches
   *
   * @return array
   */
  protected function splitHighlights(string $code, $hi): array {
    $buf = $code;
    $hiPat = $hi[0] . '^(.*)(' . substr($hi, 1, -1) . ')' . $hi[0] . 'msU';
    $hiParts = [];
    while (!empty($buf)) {
      if (preg_match($hiPat, $buf, $matches)) {
        $hiParts[] = [$matches[1], OutputInterface::OUTPUT_RAW];
        $hiParts[] = ['<info>' . $matches[2] . '</info>', OutputInterface::OUTPUT_NORMAL];
        $buf = substr($buf, strlen($matches[0]));
      }
      else {
        $hiParts[] = [$buf, OutputInterface::OUTPUT_RAW];
        $buf = NULL;
      }
    }
    return $hiParts;
  }

}
