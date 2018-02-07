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
 * Class AddCodeceptionConfigCommand
 *
 * @package CRM\CivixBundle\Command
 */
class AddCodeceptionConfigCommand extends \Symfony\Component\Console\Command\Command {

  protected function configure() {
    $this
      ->setName('generate:codeception-config')
      ->setDescription('Add a new End-to-end Configuration for codeception to a CiviCRM Module-Extension')
      ->setHelp('
Add a new test configuration for Codeception in a CiviCRM Module-Extension

This sets up a minimal codeception environment for the local CiviCRM. 
CiviCRM will be bootstrapped, and in your classes or scenarios you will be 
logged in automatically with your credentials. 

More information for building tests with codeception can be found here:
    https://codeception.com/docs/01-Introduction
An example is created in tests/acceptance/CreateContactCest.php

To execute tests, call codecept.phar (best from buildkit), e.g.

  codecept run --steps
  
');
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
      $output->writeln(sprintf('<info>Writing Codeception configuration %s/codeception.yml</info>', $destinationPath));
      $output->writeln(sprintf('<info>Writing Codeception Files to %s/tests</info>', $destinationPath));
    } catch (IOExceptionInterface $e) {
      echo "An error occurred while creating your directory at ".$e->getPath();
    }
  }

}
