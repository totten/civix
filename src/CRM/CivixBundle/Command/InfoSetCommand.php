<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use Symfony\Component\Console\Command\Command;
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

class InfoSetCommand extends Command {

  protected function configure() {
    $fields = [
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
    ];

    $this
      ->setName('info:set')
      ->setDescription('Set a field in the info.xml file')
      ->addOption('xpath', 'x', InputOption::VALUE_REQUIRED, 'The XPath expression to search on')
      ->addOption('to', 't', InputOption::VALUE_REQUIRED, 'The value of the field')
      ->setHelp("Set a single field in the info.xml file.

Examples:
  civix info:set -x version --to 2.0.0
  civix info:set -x maintainer/author --to 'Frank Lloyd Wright'
  civix info:set -x maintainer/email --to 'flloyd@example.com'

Common fields:
  * " . implode("\n  * ", $fields) . "\n");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $basedir = new Path(\CRM\CivixBundle\Application::findExtDir());
    $info = new Info($basedir->string('info.xml'));
    $ctx = [];
    $info->load($ctx);
    $info->get();

    $elements = $info->get()->xpath($input->getOption('xpath'));
    if (empty($elements)) {
      $output->writeln("Error: Path (" . $input->getOption('xpath') . ") did not match any elements.");
      return 1;
    }
    foreach ($elements as $element) {
      $element[0] = $input->getOption('to');
    }

    $info->save($ctx, $output);
  }

}
