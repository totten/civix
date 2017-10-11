<?php
namespace CRM\CivixBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Utils\Path;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Class AddCodeceptionTestCommand
 *
 * @package CRM\CivixBundle\Command
 */
class AddCodeceptionTestCommand extends \Symfony\Component\Console\Command\Command {
  
  protected function configure() {
    $this
      ->setName('generate:end2end-test')
      ->setDescription('Add a new End-to-end Test with codeception to a CiviCRM Module-Extension')
      ->setHelp('
Add a new end-to-end test with Codeception for a CiviCRM Module-Extension

This sets up a minimal codeception environment for the local Civicrm. 
Civicrm will be bootstrapped, and in your classes or scenarios you will be 
logged in automatically with your credentials. 

Codeception Test class-names must end with Cest.

More information for building tests with codeception can be found here:
    https://codeception.com/docs/01-Introduction
An example is created in tests/acceptance/CreateContactCest.php

To execute tests, call codeception.phar (best from buildkit), e.g.

  codeception run --steps
  
')
      ->addArgument('<CRM_Full_ClassName>', InputArgument::REQUIRED, 'The full class name (eg "CRM_Myextension_MyCest" or "Civi\Myextension\MyCest")');
  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $ctx = array();
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);

    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);
    if ($info->getType() != 'module') {
      $output->writeln('<error>Wrong extension type: ' . $info->getType() . '</error>');
      return;
    }
    $fs = new Filesystem();
    $sourcePath = __DIR__ . '/../Resources/codeception';
    $destinationPath = $basedir->string();
    try {
      $fs->mirror($sourcePath, $destinationPath);
      $output->writeln(sprintf('<info>Writing Codeception configuration %s/configuration.yml</info>', $destinationPath));
      $output->writeln(sprintf('<info>Writing Codeception Files to %s/tests</info>', $destinationPath));
    } catch (IOExceptionInterface $e) {
      echo "An error occurred while creating your directory at ".$e->getPath();
    }
  }

}
