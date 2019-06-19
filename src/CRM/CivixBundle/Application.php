<?php
namespace CRM\CivixBundle;

use CRM\CivixBundle\Command\AddAngularDirectiveCommand;
use CRM\CivixBundle\Command\AddAngularModuleCommand;
use CRM\CivixBundle\Command\AddAngularPageCommand;
use CRM\CivixBundle\Command\AddApiCommand;
use CRM\CivixBundle\Command\AddCaseTypeCommand;
use CRM\CivixBundle\Command\AddCustomDataCommand;
use CRM\CivixBundle\Command\AddEntityCommand;
use CRM\CivixBundle\Command\AddEntityBoilerplateCommand;
use CRM\CivixBundle\Command\AddFormCommand;
use CRM\CivixBundle\Command\AddPageCommand;
use CRM\CivixBundle\Command\AddReportCommand;
use CRM\CivixBundle\Command\AddSearchCommand;
use CRM\CivixBundle\Command\AddCodeceptionConfigCommand;
use CRM\CivixBundle\Command\AddTestCommand;
use CRM\CivixBundle\Command\AddThemeCommand;
use CRM\CivixBundle\Command\AddUpgraderCommand;
use CRM\CivixBundle\Command\BuildCommand;
use CRM\CivixBundle\Command\ConfigGetCommand;
use CRM\CivixBundle\Command\ConfigSetCommand;
use CRM\CivixBundle\Command\InfoGetCommand;
use CRM\CivixBundle\Command\InfoSetCommand;
use CRM\CivixBundle\Command\InitCommand;
use CRM\CivixBundle\Command\PingCommand;
use CRM\CivixBundle\Command\TestRunCommand;

class Application extends \Symfony\Component\Console\Application {

  /**
   * Primary entry point for execution of the standalone command.
   */
  public static function main($binDir) {
    $application = new Application('civix', '@package_version@');
    $application->run();
  }

  public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN') {
    parent::__construct($name, $version);
    $this->setCatchExceptions(TRUE);
    $this->addCommands($this->createCommands());
  }

  /**
   * Construct command objects
   *
   * @return array of Symfony Command objects
   */
  public function createCommands($context = 'default') {
    $commands = [];
    $commands[] = new AddAngularDirectiveCommand();
    $commands[] = new AddAngularModuleCommand();
    $commands[] = new AddAngularPageCommand();
    $commands[] = new AddApiCommand();
    $commands[] = new AddCaseTypeCommand();
    $commands[] = new AddCustomDataCommand();
    $commands[] = new AddEntityCommand();
    $commands[] = new AddEntityBoilerplateCommand();
    $commands[] = new AddFormCommand();
    $commands[] = new AddPageCommand();
    $commands[] = new AddReportCommand();
    $commands[] = new AddSearchCommand();
    $commands[] = new AddTestCommand();
    $commands[] = new AddThemeCommand();
    $commands[] = new AddCodeceptionConfigCommand();
    $commands[] = new AddUpgraderCommand();
    $commands[] = new BuildCommand();
    $commands[] = new ConfigGetCommand();
    $commands[] = new ConfigSetCommand();
    $commands[] = new InitCommand();
    $commands[] = new PingCommand();
    $commands[] = new TestRunCommand();
    $commands[] = new InfoGetCommand();
    $commands[] = new InfoSetCommand();
    return $commands;
  }

  /**
   * Find the base path of the current extension
   *
   * @return string
   *   Ex: "/var/www/extension/org.example.foobar".
   */
  public static function findExtDir() {
    $cwd = rtrim(getcwd(), '/');
    if (file_exists("$cwd/info.xml")) {
      return $cwd;
    }
    else {
      throw new \RuntimeException("Failed to find \"info.xml\" ($cwd/). Are you running in the right directory?");
    }
  }

}
