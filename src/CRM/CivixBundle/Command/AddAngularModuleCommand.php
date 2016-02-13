<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\CopyClass;
use CRM\CivixBundle\Builder\CopyFile;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\PhpData;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;

class AddAngularModuleCommand extends ContainerAwareCommand {

  protected function configure() {
    $this
      ->setName('generate:angular-module')
      ->setDescription('Add a new Angular module (Civi v4.6+)')
      ->addOption('am', NULL, InputOption::VALUE_REQUIRED, 'Name of the Angular module (default: match the Civi module name)');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    //// Figure out template data ////
    $ctx = array();
    $ctx['type'] = 'module';
    $ctx['basedir'] = rtrim(getcwd(), '/');
    $basedir = new Path($ctx['basedir']);

    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);
    $attrs = $info->get()->attributes();
    if ($attrs['type'] != 'module') {
      $output->writeln('<error>Wrong extension type: ' . $attrs['type'] . '</error>');
      return;
    }

    $ctx['angularModuleName'] = $input->getOption('am') ? $input->getOption('am') : $ctx['mainFile'];
    $ctx['angularModulePhp'] = $basedir->string('ang', $ctx['angularModuleName'] . '.ang.php');
    $ctx['angularModuleJs'] = $basedir->string('ang', $ctx['angularModuleName'] . '.js');
    $ctx['angularModuleCss'] = $basedir->string('ang', $ctx['angularModuleName'] . '.css');

    //// Construct files ////
    $output->writeln("<info>Initialize Angular module \"" . $ctx['angularModuleName'] . "\"</info>");

    $ext = new Collection();
    $ext->builders['dirs'] = new Dirs(array(
      dirname($ctx['angularModulePhp']),
      dirname($ctx['angularModuleJs']),
    ));;

    if (!file_exists($ctx['angularModulePhp'])) {
      $angModMeta = array(
        'js' => array(
          'ang/' . $ctx['angularModuleName'] . '.js',
          'ang/' . $ctx['angularModuleName'] . '/*.js',
          'ang/' . $ctx['angularModuleName'] . '/*/*.js',
        ),
        'css' => array(
          'ang/' . $ctx['angularModuleName'] . '.css',
        ),
        'partials' => array(
          'ang/' . $ctx['angularModuleName'],
        ),
        'settings' => array(),
      );
      $header = "// This file declares an Angular module which can be autoloaded\n"
        . "// in CiviCRM. See also:\n"
        . "// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules\n";
      $ext->builders['mgd.php'] = new PhpData($ctx['angularModulePhp'], $header);
      $ext->builders['mgd.php']->set($angModMeta);
    }

    $ext->builders['js'] = new Template('angular-module.js.php', $ctx['angularModuleJs'], FALSE, Services::templating());
    $ext->builders['css'] = new Template('angular-module.css.php', $ctx['angularModuleCss'], FALSE, Services::templating());

    $ext->init($ctx);
    $ext->save($ctx, $output);
  }

}
