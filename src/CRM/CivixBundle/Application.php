<?php
namespace CRM\CivixBundle;

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
    $commands[] = new Command\AddAngularDirectiveCommand();
    $commands[] = new Command\AddAngularModuleCommand();
    $commands[] = new Command\AddAngularPageCommand();
    $commands[] = new Command\AddApiCommand();
    $commands[] = new Command\AddCaseTypeCommand();
    $commands[] = new Command\AddCustomDataCommand();
    $commands[] = new Command\AddEntityCommand();
    $commands[] = new Command\AddFormCommand();
    $commands[] = new Command\AddManagedEntityCommand();
    $commands[] = new Command\AddPageCommand();
    $commands[] = new Command\AddReportCommand();
    $commands[] = new Command\AddSearchCommand();
    $commands[] = new Command\AddServiceCommand();
    $commands[] = new Command\AddTestCommand();
    $commands[] = new Command\AddThemeCommand();
    $commands[] = new Command\AddUpgraderCommand();
    $commands[] = new Command\BuildCommand();
    $commands[] = new Command\ConfigGetCommand();
    $commands[] = new Command\ConfigSetCommand();
    $commands[] = new Command\InitCommand();
    $commands[] = new Command\MixinCommand();
    $commands[] = new Command\ConvertEntityCommand();
    $commands[] = new Command\PingCommand();
    $commands[] = new Command\TestRunCommand();
    $commands[] = new Command\UpgradeCommand();
    $commands[] = new Command\InfoGetCommand();
    $commands[] = new Command\InfoSetCommand();
    $commands[] = new Command\InspectFunctionCommand();
    return $commands;
  }

  /**
   * Find the base path of the current extension
   *
   * @return string
   *   Ex: "/var/www/extension/org.example.foobar".
   * @deprecated
   * @see \Civix::extDir()
   */
  public static function findExtDir(): string {
    return (string) \Civix::extDir();
  }

  /**
   * @return string
   * @deprecated
   */
  public static function findCivixDir(): string {
    return (string) \Civix::appDir();
  }

}
