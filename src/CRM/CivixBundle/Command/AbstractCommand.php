<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Info;
use Civix;
use CRM\CivixBundle\Utils\Path;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command {

  protected function configure() {
    $this->addOption('yes', NULL, InputOption::VALUE_NONE, '(DEPRECATED) Alias for --no-interaction. All questions confirmed with their default choices.');
  }

  protected function initialize(InputInterface $input, OutputInterface $output) {
    if ($input->hasOption('yes') && $input->getOption('yes')) {
      $output->writeln('<error>ALERT</error>: <comment>The option "--yes" is a deprecated alias. Please use "--no-interaction" or "-n".</comment>');
      // IMHO, `--yes` is a more intuitive name. However, it implies that prompts are boolean, and it's
      // a little ambiguous what it means for multiple-choice questions.
      // By comparison, `--no-interaction` is the standardized name from Symfony Console. It's less ambiguous.
      // But at least
      $input->setOption('no-interaction', TRUE);
      $input->setInteractive(FALSE);
    }
    parent::initialize($input, $output);
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
