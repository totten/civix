<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Utils\Path;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class AddEntityBoilerplateCommand extends \Symfony\Component\Console\Command\Command {
  const API_VERSION = 3;

  use \CRM_CivixBundle_Resources_Example_SchemaBuilderTrait;

  protected function configure() {
    $this
      ->setName('generate:entity-boilerplate')
      ->setDescription('Generates boilerplate code for entities based on xml schema definition files (*EXPERIMENTAL AND INCOMPLETE*)')
      ->setHelp(
        "Creates DAOs, mysql install and uninstall instructions, and an appropriate\n" .
        "hook_civicrm_entityTypes based on this extension's xml schema files.\n" .
        "\n" .
        "Typically you will run this command after creating or updating one or more\n" .
        "xml/schema/CRM/NameSpace/EntityName.xml files.\n"
      );
  }

  /**
   * Note: this function replicates a fair amount of the functionality of
   * CRM_Core_CodeGen_Specification (which is a bit messy and hard to interact
   * with). It's tempting to completely rewrite / rethink entity generation. Until
   * then...
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    Services::boot(['output' => $output]);
    $civicrm_api3 = Services::api3();

    if (!$civicrm_api3 || !$civicrm_api3->local) {
      $output->writeln("<error>Require access to local CiviCRM source tree. Configure civicrm_api3_conf_path.</error>");
      return 1;
    }
    if (version_compare(\CRM_Utils_System::version(), '4.7.0', '<=')) {
      $output->writeln("<error>This command requires CiviCRM 4.7+.</error>");
      return 1;
    }

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);
    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);

    $deprecated = $this->findDeprecatedFiles($basedir);
    if ($deprecated) {
      foreach ($deprecated as $file) {
        $output->writeln("<error>Found deprecated SQL file: $file</error>");
      }
      $output->writeln("To better adapt to local MySQL configurations, schema will now be generated on the fly.");
      $output->writeln("The old static files will be removed to avoid double-installation.");
      $output->writeln("However, there will be example files that you may inspect to preview the effective schema.");

      $helper = $this->getHelper('question');
      $question = new ConfirmationQuestion('<comment>Continue? (Y/n)</comment> ', TRUE);

      if (!$helper->ask($input, $output, $question)) {
        $output->writeln("<error>Aborting</error>");
        return 1;
      }

      foreach ($deprecated as $file) {
        unlink($file);
      }
    }

    $files = $this->createSchemaBuilder($ctx)
      ->addXml("xml/schema/{$ctx['namespace']}/*.xml")
      ->generateDaoFiles()
      ->generateSqlFile('CREATE', $basedir->string('sql/create_example.sql'))
      ->generateSqlFile('DROP', $basedir->string('sql/drop_example.sql'))
      ->getFiles();

    foreach ($files as $file) {
      $output->writeln(sprintf("<info>Write %s</info>", $file['file']));
    }

    $module = new Module(Services::templating());
    $module->loadInit($ctx);
    $module->save($ctx, $output);
    $upgraderClass = str_replace('/', '_', $ctx['namespace']) . '_Upgrader';

    if (!class_exists($upgraderClass)) {
      $output->writeln('<error>You are missing an upgrader class. Your generated SQL files will not be executed on enable and uninstall. Fix this by running `civix generate:upgrader`.</error>');
    }

    $expectMethods = ['createSchemaBuilder'];
    $upgraderClazz = new \ReflectionClass($upgraderClass);
    foreach ($expectMethods as $expectMethod) {
      if (!$upgraderClazz->hasMethod($expectMethod)) {
        $output->writeln("<error>The upgrader is missing a method ($expectMethod). Fix this by running `civix generate:upgrader`.</error>");
      }
    }
  }

  /**
   * @param \CRM\CivixBundle\Utils\Path $basedir
   */
  protected function findDeprecatedFiles(Path $basedir): array {
    $matches = [];
    $candidates = [
      $basedir->string('sql/auto_install.sql'),
      $basedir->string('sql/auto_uninstall.sql'),
    ];
    foreach ($candidates as $file) {
      if (file_exists($file)) {
        $matches[] = $file;
      }
    }
    return $matches;
  }

}
