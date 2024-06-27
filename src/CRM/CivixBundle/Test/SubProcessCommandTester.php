<?php

namespace CRM\CivixBundle\Test;

use Symfony\Component\Process\Process;

class SubProcessCommandTester implements CommandTester {

  /**
   * @var array
   */
  protected $baseCommand;

  /**
   * @var string|null
   */
  protected $display;

  /**
   * @var int|null
   */
  protected $statusCode;

  /**
   * @param array $baseCommand
   */
  public function __construct(array $baseCommand) {
    $this->baseCommand = $baseCommand;
  }

  /**
   * Executes the command.
   *
   * @param array $input An array of command arguments and options
   * @param array $options An array of execution options
   *   Ignored
   * @return int The command exit code
   */
  public function execute(array $input, array $options = []) {
    if (!empty($options)) {
      throw new \LogicException(__CLASS__ . " does not implement support for execute() options");
    }

    $command = $this->baseCommand;
    foreach ($input as $key => $value) {
      if (substr($key, 0, 2) === '--') {
        if ($value === TRUE) {
          $command[] = $key;
        }
        else {
          $command[] = "$key=$value";
        }
      }
      else {
        $command[] = $value;
      }
    }

    $buffer = fopen('php://memory', 'w+');

    $p = new Process($command);
    $p->run(function ($type, $data) use ($buffer) {
      // Default policy - combine STDOUT and STDIN into one continuous stream.
      fwrite($buffer, $data);
    });
    $this->statusCode = $p->getExitCode();

    rewind($buffer);
    $this->display = stream_get_contents($buffer);
    fclose($buffer);

    return $this->statusCode;
  }

  public function getDisplay(bool $normalize = FALSE) {
    if ($normalize) {
      return str_replace(\PHP_EOL, "\n", $this->display);
    }
    else {
      return $this->display;
    }
  }

  public function getStatusCode(): int {
    return $this->statusCode;
  }

}
