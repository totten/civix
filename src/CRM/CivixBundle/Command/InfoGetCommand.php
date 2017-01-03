<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Builder\PhpData;
use CRM\CivixBundle\Utils\Path;
use Exception;

class InfoGetCommand extends Command {

  protected function configure() {
    $fields = array(
      'file',
      'name',
      'description',
      'license',
      'releaseDate',
      'version',
      'develStage',
      'comments',
      'maintainer/author',
      'maintainer/email',
      'civix/namespace',
      'urls/url[@desc="Documentation"]',
      'urls/url[@desc="Support"]',
    );

    $this
      ->setName('info:get')
      ->setDescription('Read a field from the info.xml file')
      ->addOption('xpath', 'x', InputOption::VALUE_REQUIRED, '(REQUIRED) The XPath expression of the field')
      ->setHelp("Read a single field from the info.xml file.

Examples:
  civix info:get -x version
  civix info:get -x maintainer/author
  civix info:get -x maintainer/email

Common fields:\n * " . implode("\n * ", $fields) . "\n");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $basedir = new Path(\CRM\CivixBundle\Application::findExtDir());
    $info = new Info($basedir->string('info.xml'));
    $ctx = array();
    $info->load($ctx);
    $info->get();

    $xpath = $input->getOption('xpath');
    if (is_null($xpath)) {
      // missing xpath value so provide help
      $help = $this->getApplication()->get('help');
      $help->setCommand($this); // tell help to provide specific help for this function
      return $help->run($input, $output);
    }
    foreach ($info->get()->xpath($xpath) as $node) {
      echo (string) $node;
      echo "\n";
    }
  }

}
