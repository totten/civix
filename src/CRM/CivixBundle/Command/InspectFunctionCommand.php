<?php

namespace CRM\CivixBundle\Command;

use Civix;
use CRM\CivixBundle\Parse\PrimitiveFunctionVisitor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InspectFunctionCommand extends AbstractCommand {

  const SKIP = '/^\./';

  const PHP_FILES = '/\.(php|module|inc|install)$/';

  protected function configure() {
    parent::configure();
    $this
      ->setName('inspect:fun')
      ->setDescription('Search codebase for functions')
      ->addOption('name', NULL, InputOption::VALUE_REQUIRED, 'Pattern describing the function-names you wnt to see')
      ->addOption('body', NULL, InputOption::VALUE_REQUIRED, 'Pattern describing function bodies that you want to see')
      ->addOption('files-with-matches', 'l', InputOption::VALUE_NONE, 'Print only file names')
      ->addOption('file-size-max', NULL, InputOption::VALUE_REQUIRED, 'Only scan files within this limit (KB)', 1024)
      ->addArgument('files', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'List of files')
      ->setHelp('Search PHP functions

Example: Find all functions named like "_civicrm_permission"
  civix inspect:fun --name=/_civicrm_permission/ *.php

Example: Find all functions which call civicrm_api3()
  civix inspect:fun --body=/civicrm_api3/ *.php

Example: Find all functions named like "_civicrm_permission" AND having a body with "label"
  civix inspect:fun --name=/_civicrm_permission/ --body=/label/ *.php
');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $functionNamePattern = $input->getOption('name');
    if ($functionNamePattern) {
      $this->assertRegex($functionNamePattern, '--name');
    }
    $bodyPattern = $input->getOption('body');
    if ($bodyPattern) {
      $this->assertRegex($bodyPattern, '--body');
    }
    $printer = [$this, 'printMatch'];
    if ($input->getOption('files-with-matches')) {
      $printer = [$this, 'printFileName'];
    }

    $todo = $input->getArgument('files');
    sort($todo);
    while (!empty($todo)) {
      $file = array_shift($todo);
      $file = rtrim($file, DIRECTORY_SEPARATOR . '/');

      if ($output->isVeryVerbose()) {
        $output->writeln("## SCAN FILE: $file");
      }

      if (preg_match(static::SKIP, basename($file)) || is_link($file)) {
        continue;
      }
      if (is_dir($file)) {
        $todo = array_merge($todo, glob("$file/*"));
        sort($todo);
        continue;
      }
      if (!preg_match(self::PHP_FILES, $file)) {
        continue;
      }
      $size = ceil(filesize($file) / 1024);
      if ($size > $input->getOption('file-size-max')) {
        $output->writeln(sprintf('<error>WARNING</error> Skip file "%s". Size (%d KB) exceeds limit (%s KB)', $file, $size, $input->getOption('file-size-max')));
        continue;
      }

      $fileContent = file_get_contents($file);
      PrimitiveFunctionVisitor::visit($fileContent, function (?string &$functionName, string &$signature, string &$body) use ($bodyPattern, $functionNamePattern, $file, $input, $printer) {
        if ($functionNamePattern && !preg_match($functionNamePattern, $functionName)) {
          return;
        }
        if ($bodyPattern && !preg_match($bodyPattern, $body)) {
          return;
        }

        $printer($file, $functionName, $signature, $body, $bodyPattern);
      });
    }

    return 0;
  }

  protected function printFileName($file, ?string $functionName, string $signature, string $code, $codePattern) {
    Civix::output()->writeln($file, OutputInterface::OUTPUT_RAW);
  }

  protected function printMatch($file, ?string $functionName, string $signature, string $body, $bodyPattern): void {
    Civix::output()->writeln(sprintf("## FILE: %s", $file));
    Civix::output()->write(sprintf("<comment>function <info>%s</info>(%s)</comment> {", $functionName, $signature));
    if (!$bodyPattern) {
      Civix::output()->write($body, FALSE, OutputInterface::OUTPUT_RAW);
    }
    else {
      $hiParts = $this->splitHighlights($body, $bodyPattern);
      foreach ($hiParts as $part) {
        Civix::output()->write($part[0], FALSE, $part[1]);
      }
    }
    Civix::output()->write("}\n\n");
  }

  /**
   * Split a block of $code into highlighted and non-highlighted sections.
   *
   * @param string $code
   *   The code to search/highlight
   * @param string $hi
   *   Regex identifying the expressions to highlight
   * @return array
   */
  protected function splitHighlights(string $code, $hi): array {
    $buf = $code;
    $delimQuot = preg_quote($hi[0], ';');
    if (preg_match(';' . $delimQuot . '([a-zA-Z]+)$;', $hi, $m)) {
      $modifiers = $m[1];
      $hi = substr($hi, 0, -1 * strlen($modifiers));
    }
    else {
      $modifiers = '';
    }

    $hiPat = $hi[0] . '^(.*)(' . substr($hi, 1, -1) . ')' . $hi[0] . 'msU' . $modifiers;
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

  /**
   * Assert that $regex is a plausible-looking regular expression.
   *
   * @param string $regex
   * @param string $regexOption
   */
  protected function assertRegex(string $regex, string $regexOption): void {
    $delim = $regex[0];
    $delimQuote = preg_quote($delim, ';');
    $allowDelim = '/|:;,.#';
    if (strpos($allowDelim, $delim) === FALSE) {
      throw new \Exception("Option \"$regexOption\" should have a symbolic delimiter, such as: $allowDelim");
    }
    if (!preg_match(';^' . $delimQuote . '.*' . $delimQuote . '[a-zA-Z]*$;', $regex)) {
      throw new \Exception("Option \"$regexOption\" should be well-formed");
    }
  }

}
