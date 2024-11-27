<?php
namespace CRM\CivixBundle\Command;

use Civix;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\CustomDataXML;
use CRM\CivixBundle\Utils\Path;

class AddCustomDataCommand extends AbstractCommand {

  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:custom-xml')
      ->setDescription('Export custom data and profiles to an XML file')
      ->addArgument('<CustomDataFile.xml>', InputArgument::OPTIONAL, 'The path to write custom data to (default: xml/auto_install.xml)')
      ->addOption('data', NULL, InputOption::VALUE_REQUIRED, 'Comma-separated list of custom data group IDs (from linked dev site)')
      ->addOption('uf', NULL, InputOption::VALUE_REQUIRED, 'Comma-separated list of profile group IDs (from linked dev site)')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing files');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    // load Civi to get access to civicrm_api_get_function_name
    Civix::boot(['output' => $output]);
    $civicrm_api3 = Civix::api3();
    if (!$civicrm_api3 || !$civicrm_api3->local) {
      $output->writeln("<error>generate:custom-xml requires access to local CiviCRM instance. Configure civicrm_api3_conf_path.</error>");
      return 1;
    }

    $this->assertCurrentFormat();

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);

    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);

    $dirs = new Dirs([
      $basedir->string('xml'),
    ]);
    $dirs->save($ctx, $output);

    if ($input->getArgument('<CustomDataFile.xml>')) {
      $customDataXMLFile = $basedir->string($input->getArgument('<CustomDataFile.xml>'));
    }
    else {
      $customDataXMLFile = $basedir->string('xml', 'auto_install.xml');
    }
    if (!$input->getOption('data') && !$input->getOption('uf')) {
      $output->writeln("<error>generate:custom-xml requires --data and/or --uf</error>");
      return 1;
    }
    $customDataXML = new CustomDataXML(
      $input->getOption('data') ? explode(',', $input->getOption('data')) : [],
      $input->getOption('uf') ? explode(',', $input->getOption('uf')) : [],
      $customDataXMLFile,
      $input->getOption('force')
    );
    $customDataXML->save($ctx, $output);

    if (preg_match('/\/xml\/.*_install.xml$/', $customDataXMLFile)) {
      $output->writeln(" * NOTE: This filename ends with \"_install.xml\". If you would like to load it automatically on new sites, then make sure there is an install/upgrade class (i.e. run \"civix generate:upgrader\")");
    }
    else {
      $output->writeln(" * NOTE: By default, this file will not be loaded automatically -- you must define installation or upgrade logic to load the file.");
    }

    return 0;
  }

}
