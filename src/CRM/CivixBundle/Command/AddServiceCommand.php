<?php

namespace CRM\CivixBundle\Command;

use Civix;
use CRM\CivixBundle\Utils\Naming;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddServiceCommand extends AbstractCommand {

  protected function configure() {
    Civix::templating();
    $this
      ->setName('generate:service')
      ->setDescription('Create a new service')
      ->addArgument('name', InputArgument::OPTIONAL, 'Machine-name for the service')
      ->addOption('naming', NULL, InputOption::VALUE_OPTIONAL, 'Force the service-class to use CRM- or Civi-style naming', 'auto')
      ->setHelp('');
    parent::configure();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $up = $this->getUpgrader();
    $up->addMixins(['scan-classes@1.0']);

    $servicePrefix = $up->infoXml->getFile();
    $namespace = Naming::coerceNamespace($up->infoXml->getNamespace(), $input->getOption('naming'));

    if ($input->isInteractive()) {
      $defaultName = $input->getArgument('name') ?? Naming::createServiceName($servicePrefix, 'myService');
      Civix::io()->note([
        'The service name is a short machine name. It may appear in contexts like:',
        sprintf('Civi::service("%s")->doSomething()', $defaultName),
        sprintf('It is recommended to always have a naming prefix (such as "%s").', $servicePrefix),
      ]);
      $serviceName = Civix::io()->ask('Service name', $defaultName, function ($answer) {
        if ('' === trim($answer)) {
          throw new \Exception('Service name cannot be empty');
        }
        return $answer;
      });
    }
    else {
      $serviceName = $input->getArgument('name');
      if ('' === trim($serviceName)) {
        throw new \Exception('Service name cannot be empty');
      }
    }

    $baseName = Naming::removeServicePrefix($servicePrefix, $serviceName);
    $baseNameParts = array_map('ucfirst', explode('.', $baseName));
    $className = Naming::createClassName($namespace, ...$baseNameParts);

    $up->addClass($className, 'service.php.php', [
      'service' => $serviceName,
    ]);
  }

}
