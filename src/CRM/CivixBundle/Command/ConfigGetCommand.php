<?php
namespace CRM\CivixBundle\Command;

use Civix;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigGetCommand extends AbstractCommand {

  protected function configure() {
    parent::configure();
    $this
      ->setName('config:get')
      ->setDescription('Get configuration values')
      ->addArgument('parameter', InputArgument::OPTIONAL, 'Parameter name');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $config = Civix::config();
    foreach ($this->getInterestingParameters() as $key) {
      printf("%-40s \"%s\"\n", $key, @$config['parameters'][$key]);
    }
    return 0;
  }

  protected function getInterestingParameters() {
    return [
      'author',
      'email',
      'license',
    ];
  }

}
