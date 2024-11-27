<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Mixins;
use Civix;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\PhpData;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;

class AddAngularModuleCommand extends AbstractCommand {

  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:angular-module')
      ->setDescription('Add a new Angular module')
      ->addOption('am', NULL, InputOption::VALUE_REQUIRED, 'Name of the Angular module (default: match the Civi module name)');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->assertCurrentFormat();

    //// Figure out template data ////
    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);
    $info = $this->getModuleInfo($ctx);

    $ctx['angularModuleName'] = $input->getOption('am') ? $input->getOption('am') : $ctx['angularModuleName'];
    $ctx['angularModulePhp'] = $basedir->string('ang', $ctx['angularModuleName'] . '.ang.php');
    $ctx['angularModuleJs'] = $basedir->string('ang', $ctx['angularModuleName'] . '.js');
    $ctx['angularModuleCss'] = $basedir->string('ang', $ctx['angularModuleName'] . '.css');

    //// Construct files ////
    $output->writeln("<info>Initialize Angular module</info> " . $ctx['angularModuleName']);

    $ext = new Collection();
    $ext->builders['dirs'] = new Dirs([
      dirname($ctx['angularModulePhp']),
      dirname($ctx['angularModuleJs']),
    ]);;

    if (!file_exists($ctx['angularModulePhp'])) {
      $angModMeta = [
        'js' => [
          'ang/' . $ctx['angularModuleName'] . '.js',
          'ang/' . $ctx['angularModuleName'] . '/*.js',
          'ang/' . $ctx['angularModuleName'] . '/*/*.js',
        ],
        'css' => [
          'ang/' . $ctx['angularModuleName'] . '.css',
        ],
        'partials' => [
          'ang/' . $ctx['angularModuleName'],
        ],
        'requires' => ['crmUi', 'crmUtil', 'ngRoute'],
        'settings' => [],
      ];
      $header = "// Angular module $ctx[angularModuleName].\n"
        . "// @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules";
      $ext->builders['ang.php'] = new PhpData($ctx['angularModulePhp'], $header);
      $ext->builders['ang.php']->set($angModMeta);
    }

    $ext->builders['js'] = new Template('angular-module.js.php', $ctx['angularModuleJs'], FALSE, Civix::templating());
    $ext->builders['css'] = new Template('angular-module.css.php', $ctx['angularModuleCss'], FALSE, Civix::templating());

    $ext->builders['mixins'] = new Mixins($info, $basedir->string('mixin'), ['ang-php@1.0']);
    $ext->builders['info'] = $info;

    $ext->loadInit($ctx);
    $ext->save($ctx, $output);
    return 0;
  }

}
