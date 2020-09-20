<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\PHPUnitGenerateInitFiles;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Builder\PhpData;
use CRM\CivixBundle\Utils\Path;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddApiCommand extends Command {
  const API_VERSION = 3;

  public static function getSchedules() {
    return ['Daily', 'Hourly', 'Always'];
  }

  protected function configure() {
    $this
      ->setName('generate:api')
      ->setDescription('Add a new API function to a CiviCRM Module-Extension')
      ->addArgument('<EntityName>', InputArgument::REQUIRED, 'The entity against which the action runs (eg "Contact", "MyEntity")')
      ->addArgument('<actionname>', InputArgument::REQUIRED, 'The action which will be created (eg "create", "myaction")')
      ->addOption('schedule', NULL, InputOption::VALUE_OPTIONAL, 'Schedule this action as a recurring cron job (' . implode(', ', self::getSchedules()) . ') [For CiviCRM 4.3+]')
      ->setHelp('Add a new API function to a CiviCRM Module-Extension

This will generate an API entity/action with a standalone PHP file and test-class.

Note: APIv3 naming conventions are somewhat conflicted. In theory, callers may use
entity-names and action-names expressed interchangably as CamelCase or under_score_case.
In practice:

- Entity names are interchangeable, though we typically regard CamelCase as canonical.
- Action names are tempermental -- working+non-working combinations depend on multiple
  variables. The simplest approach believed to work consistently is to use one-word
  actions (e.g. "Getcount"/"getcount" instead of "GetCount"/"getCount"/"get_count").

In keeping, "civix generate:api" expects CamelCase entity names and singleword
action names.
');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);

    // load Civi to get access to civicrm_api_get_function_name
    Services::boot(['output' => $output]);
    $civicrm_api3 = Services::api3();
    if (!$civicrm_api3 || !$civicrm_api3->local) {
      $output->writeln("<error>--copy requires access to local CiviCRM source tree. Configure civicrm_api3_conf_path.</error>");
      return;
    }

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);

    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);
    $attrs = $info->get()->attributes();
    if ($attrs['type'] != 'module') {
      $output->writeln('<error>Wrong extension type: ' . $attrs['type'] . '</error>');
      return;
    }

    if (!preg_match('/^[A-Za-z0-9]+$/', $input->getArgument('<EntityName>'))) {
      throw new Exception("Entity name must be alphanumeric camel-case");
    }
    if (!preg_match('/^[A-Za-z][a-z0-9]*$/', $input->getArgument('<actionname>'))) {
      throw new Exception("Action name must be alphanumeric singleword");
    }
    if ($input->getOption('schedule') && !in_array($input->getOption('schedule'), self::getSchedules())) {
      throw new Exception("Schedule must be one of: " . implode(', ', self::getSchedules()));
    }

    $ctx['entityNameCamel'] = ucfirst($input->getArgument('<EntityName>'));
    $ctx['actionNameCamel'] = ucfirst($input->getArgument('<actionname>'));
    $ctx['actionNameLower'] = strtolower($input->getArgument('<actionname>'));
    // $ctx['actionNameUnderscore'] = strtolower(implode('_', array_filter(preg_split('/(?=[A-Z])/', $input->getArgument('<actionname>')))));

    if (function_exists('civicrm_api_get_function_name')) {
      $ctx['apiFunction'] = strtolower(civicrm_api_get_function_name($ctx['entityNameCamel'], $ctx['actionNameCamel'], self::API_VERSION));
    }
    elseif (function_exists('_civicrm_api_get_entity_name_from_camel')) {
      $ctx['apiFunction'] = 'civicrm_api' . self::API_VERSION . '_' . _civicrm_api_get_entity_name_from_camel($ctx['entityNameCamel']) . '_' . $ctx['actionNameCamel'];
    }
    else {
      throw new Exception("Failed to determine proper API function name. Perhaps the API internals have changed?");
    }
    $ctx['apiFile'] = $basedir->string('api', 'v3', $ctx['entityNameCamel'], $ctx['actionNameCamel'] . '.php');
    $ctx['apiCronFile'] = $basedir->string('api', 'v3', $ctx['entityNameCamel'], $ctx['actionNameCamel'] . '.mgd.php');
    $ctx['apiTestFile'] = $basedir->string('tests', 'phpunit', 'api', 'v3', $ctx['entityNameCamel'], $ctx['actionNameCamel'] . 'Test.php');

    $ctx['testClassName'] = "api_v3_{$ctx['entityNameCamel']}_{$ctx['actionNameCamel']}Test";

    $dirs = new Dirs([
      dirname($ctx['apiFile']),
    ]);
    $dirs->save($ctx, $output);

    if (!file_exists($ctx['apiFile'])) {
      $output->writeln(sprintf('<info>Write %s</info>', $ctx['apiFile']));
      file_put_contents($ctx['apiFile'], Services::templating()
        ->render('api.php.php', $ctx));
    }
    else {
      $output->writeln(sprintf('<error>Skip %s: file already exists</error>', $ctx['apiFile']));
    }

    if ($input->getOption('schedule')) {
      if (!file_exists($ctx['apiCronFile'])) {
        $mgdEntities = [
          [
            'name' => 'Cron:' . $ctx['entityNameCamel'] . '.' . $ctx['actionNameCamel'],
            'entity' => 'Job',
            'params' => [
              'version' => 3,
              'name' => sprintf('Call %s.%s API', $ctx['entityNameCamel'], $ctx['actionNameCamel']),
              'description' => sprintf('Call %s.%s API', $ctx['entityNameCamel'], $ctx['actionNameCamel']),
              'run_frequency' => $input->getOption('schedule'),
              'api_entity' => $ctx['entityNameCamel'],
              'api_action' => $ctx['actionNameCamel'],
              'parameters' => '',
            ],
          ],
        ];
        $header = "// This file declares a managed database record of type \"Job\".\n"
          . "// The record will be automatically inserted, updated, or deleted from the\n"
          . "// database as appropriate. For more details, see \"hook_civicrm_managed\" at:\n"
          . "// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed";
        $mgdBuilder = new PhpData($ctx['apiCronFile'], $header);
        $mgdBuilder->set($mgdEntities);
        $mgdBuilder->save($ctx, $output);
      }
      else {
        $output->writeln(sprintf('<error>Skip %s: file already exists</error>', $ctx['apiCronFile']));
      }
    }

    $test_dirs = new Dirs([
      dirname($ctx['apiTestFile']),
    ]);
    $test_dirs->save($ctx, $output);
    if (!file_exists($ctx['apiTestFile'])) {
      $output->writeln(sprintf('<info>Write %s</info>', $ctx['apiTestFile']));
      file_put_contents($ctx['apiTestFile'], Services::templating()
        ->render('test-api.php.php', $ctx));
    }
    else {
      $output->writeln(sprintf('<error>Skip %s: file already exists</error>', $ctx['apiTestFile']));
    }

    $module = new Module(Services::templating());
    $module->loadInit($ctx);
    $module->save($ctx, $output);

    $phpUnitInitFiles = new PHPUnitGenerateInitFiles();
    $phpUnitInitFiles->initPhpunitXml($basedir->string('phpunit.xml.dist'), $ctx, $output);
    $phpUnitInitFiles->initPhpunitBootstrap($basedir->string('tests', 'phpunit', 'bootstrap.php'), $ctx, $output);
  }

}
