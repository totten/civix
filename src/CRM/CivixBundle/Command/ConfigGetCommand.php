<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigGetCommand extends \Symfony\Component\Console\Command\Command {
  protected function configure() {
    $this
      ->setName('config:get')
      ->setDescription('Get configuration values')
      ->addArgument('parameter', InputArgument::OPTIONAL, 'Parameter name');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $config = Services::config();
    foreach ($this->getInterestingParameters() as $key) {
      printf("%-40s \"%s\"\n", $key, @$config['parameters'][$key]);
    }
  }

  protected function getInterestingParameters() {
    return array(
      'author',
      'email',
      'license',
    );
  }

}
