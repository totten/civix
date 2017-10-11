<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Utils\Path;
use CRM\CivixBundle\Builder\PHPUnitGenerateInitFiles;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class AddCodeceptionTestCommand extends \Symfony\Component\Console\Command\Command {

  protected function configure() {
    $this
      ->setName('generate:codeception-test')
      ->setDescription('Add a new Codeception test (end-to-end) to a CiviCRM Module-Extension')
      ->setHelp('
Add a new Codeception test (end-to-end) to a CiviCRM Module-Extension

This sets up a minimal codeception environment for the local civicrm. 
Civicrm will be bootstrapped, and in your classes or scenarios you will be 
logged in automatically with your credentials. 

Codeception Test class-names must end with Cest.

More information for building tests with codeception can be found here:
    https://codeception.com/docs/01-Introduction

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
    // TODO: write/copy files for codeception here
    $fs = new Filesystem();
    $sourcePath = __DIR__ . '/../Resources/codeception';
    $destinationPath = $basedir->string();
    error_log("DEBUG_Phil: {$sourcePath} --> {$destinationPath}");
    try {
      $fs->mirror($sourcePath, $destinationPath);
    } catch (IOExceptionInterface $e) {
      echo "An error occurred while creating your directory at ".$e->getPath();
    }
  }

}
