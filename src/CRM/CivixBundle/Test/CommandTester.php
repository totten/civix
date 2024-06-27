<?php

namespace CRM\CivixBundle\Test;

interface CommandTester {

  /**
   * Executes the command.
   *
   * Available execution options:
   *
   *  * interactive:               Sets the input interactive flag
   *  * decorated:                 Sets the output decorated flag
   *  * verbosity:                 Sets the output verbosity flag
   *  * capture_stderr_separately: Make output of stdOut and stdErr separately available
   *
   * @param array $input An array of command arguments and options
   * @param array $options An array of execution options
   *
   * @return int The command exit code
   */
  public function execute(array $input, array $options = []);

  /**
   * @return string
   */
  public function getDisplay(bool $normalize = FALSE);

  /**
   * @return int
   */
  public function getStatusCode();

}
