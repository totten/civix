<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\PhpUnitXML;
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

class AddTestCommand extends \Symfony\Component\Console\Command\Command {
  protected function configure() {
    $this
      ->setName('generate:test')
      ->setDescription('Add a new PHPUnit test to a CiviCRM Module-Extension')
      ->setHelp('
Add a new PHPUnit test to a CiviCRM Module-Extension

In creating a test, you may specify a template:
  headless: A headless test boots CiviCRM once with a headless database, and
            all work can be executed in-process. These are faster and support
            automatic cleanup, but they provide a less thorough simulation
            of real-world systems.
  e2e:      An end-to-end test boots the live installation of CiviCRM and
            the real CMS This provides a more thorough simulation, and you
            may spawn  requests to Civi using HTTP or cv(). However, spawning
            separate requests will be slower, and data-cleanup may take more
            effort.
  legacy:   A variation of `headless` based on CiviUnitTestCase.
            It is provided primarily for testing purposes.
  phpunit:  A test suite based on the PHPUnit_Framework_TestCase. Provides an 
            interface for you extension to implement unittest for your 
            classes/functions

To execute tests, call phpunit 4.x directly, e.g.

  phpunit4 tests/phpunit/CRM/Myextension/MyTest.php

Note: The design of headless and E2E tests prevent them from running
concurrently. If you have a mix of tests, you can execute them
as separate groups:

  phpunit4 --group headless
  phpunit4 --group e2e
')
      ->addOption('template', NULL, InputOption::VALUE_REQUIRED, 'The template of test to generate (headless, e2e, legacy)', 'headless')
      ->addArgument('<CRM_Full_ClassName>', InputArgument::REQUIRED, 'The full class name (eg "CRM_Myextension_MyTest" or "Civi\Myextension\MyTest")');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);

    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);
    if ($info->getType() != 'module') {
      $output->writeln('<error>Wrong extension type: ' . $info->getType() . '</error>');
      return;
    }

    $phpUnitInitFiles = new PHPUnitGenerateInitFiles();
    $phpUnitInitFiles->initPhpunitXml($basedir->string('phpunit.xml.dist'), $ctx, $output);
    $phpUnitInitFiles->initPhpunitBootstrap($basedir->string('tests', 'phpunit', 'bootstrap.php'), $ctx, $output);
    $this->initTestClass(
      $input->getArgument('<CRM_Full_ClassName>'), $this->getTestTemplate($input->getOption('template')), $basedir, $ctx, $output);
  }

  protected function getTestTemplate($type) {
    $templates = [
      'e2e' => 'test-e2e.php.php',
      'headless' => 'test-headless.php.php',
      'legacy' => 'test-legacy.php.php',
      'phpunit' => 'test-phpunit.php.php',
    ];
    if (isset($templates[$type])) {
      return $templates[$type];
    }
    else {
      throw new \Exception("Invalid test template");
    }
  }

  /**
   * @param string $fullClassName
   * @param string $templateName
   * @param Path $basedir
   * @param array $ctx
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @throws \Exception
   */
  protected function initTestClass($fullClassName, $templateName, $basedir, $ctx, OutputInterface $output) {
    $fullClassName = trim($fullClassName, '\\');

    if (!preg_match('/^[A-Za-z0-9_\\\\]+$/', $fullClassName)) {
      throw new Exception("Class name must be alphanumeric (with underscores and backslashes)");
    }
    if (!preg_match('/Test$/', $fullClassName)) {
      throw new Exception("Class name must end with the word \"Test\"");
    }

    $parts = explode('\\', $fullClassName);
    $ctx['testClass'] = array_pop($parts);
    $ctx['testNamespace'] = implode('\\', $parts);
    $testFile = strtr($fullClassName, ['_' => '/', '\\' => '/']) . '.php';
    $testPath = $basedir->string('tests', 'phpunit', $testFile);

    $dirs = new Dirs([
      dirname($testPath),
    ]);
    $dirs->save($ctx, $output);

    if (!file_exists($testPath)) {
      $output->writeln(sprintf('<info>Write %s</info>', $testPath));
      file_put_contents($testPath, Services::templating()
        ->render($templateName, $ctx));
    }
    else {
      $output->writeln(sprintf('<error>Skip %s: file already exists</error>', $testPath));
    }
  }

}
