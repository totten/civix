<?php

namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use CRM\CivixBundle\Utils\Naming;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddServiceCommand extends AbstractCommand {

  protected function configure() {
    Services::templating();
    $this
      ->setName('generate:service')
      ->setDescription('Create a new service')
      ->addArgument('name', InputArgument::REQUIRED, 'Short code-name for the service')
      ->addOption('naming', NULL, InputOption::VALUE_OPTIONAL, 'Force the service-class to use CRM- or Civi-style naming', 'auto')
      ->setHelp('');
    parent::configure();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $up = $this->getUpgrader();
    $service = Naming::createServiceName($this->getUpgrader()->infoXml->getNamespace(), lcfirst($input->getArgument('name')));
    $up->addMixins(['scan-classes@1.0']);
    $up->addClass(ucfirst($input->getArgument('name')), 'service.php.php', [
      'service' => $service,
    ]);
  }

}
