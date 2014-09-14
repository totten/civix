<?php
namespace CRM\CivixBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Dirs;

class ConfigGetCommand extends ContainerAwareCommand {
  protected function configure() {
    $this
      ->setName('config:get')
      ->setDescription('Get configuration values')
      ->addArgument('parameter', InputArgument::OPTIONAL, 'Parameter name');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    foreach ($this->getInterestingParameters() as $key) {
      printf("%-40s \"%s\"\n", $key, $this->getContainer()->getParameter($key));
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
