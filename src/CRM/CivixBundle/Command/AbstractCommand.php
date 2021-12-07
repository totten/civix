<?php
namespace CRM\CivixBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractCommand extends Command {
  protected function configure() {
    $this->addOption('yes', NULL, InputOption::VALUE_NONE, 'Answer yes to any questions');
  }

  protected function confirm(InputInterface $input, OutputInterface $output, $message, $default = TRUE) {
    $message = '<info>' . $message . '</info>'; /* FIXME Let caller stylize */
    if ($input->getOption('yes')) {
      $output->writeln($message . ($default ? 'Y' : 'N'));
      return $default;
    }

    /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion($message, $default);
    return (bool) $helper->ask($input, $output, $question);
  }

}
