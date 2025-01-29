<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Info;
use Civix;
use CRM\CivixBundle\Utils\Path;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractCommand extends Command {

  protected function configure() {
    $this->addOption('yes', NULL, InputOption::VALUE_NONE, 'Use the default confirmation for all questions');
  }

  public function run(InputInterface $input, OutputInterface $output) {
    try {
      \Civix::ioStack()->push($input, $output);
      return parent::run($input, $output);
    }
    finally {
      \Civix::ioStack()->pop();
    }
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

  protected function getModuleInfo(&$ctx): Info {
    $basedir = new Path(\CRM\CivixBundle\Application::findExtDir());
    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);
    $attrs = $info->get()->attributes();
    if ($attrs['type'] != 'module') {
      throw new \RuntimeException('Wrong extension type: ' . $attrs['type']);
    }
    return $info;
  }

  protected function assertCurrentFormat() {
    // Note: getModuleInfo() asserts that type is 'module'
    $info = $this->getModuleInfo($ctx);
    $actualVersion = $info->detectFormat();
    $expectedVersion = Civix::upgradeList()->getHeadVersion();
    if (version_compare($actualVersion, $expectedVersion, '<')) {
      throw new \Exception("This extension requires an upgrade for the file-format (current=$actualVersion; expected=$expectedVersion). Please run 'civix upgrade' before generating code.");
    }
  }

}
