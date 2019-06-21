<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;

class AddAngularDirectiveCommand extends \Symfony\Component\Console\Command\Command {

  protected function configure() {
    $this
      ->setName('generate:angular-directive')
      ->setDescription('Add a new Angular directive (Civi v4.6+)')
      ->addOption('am', NULL, InputOption::VALUE_REQUIRED, 'Name of the Angular module (default: match the Civi module name)')
      ->addArgument('<directive-name>', InputArgument::REQUIRED, 'Directive name (Ex: "my-directive")')
      ->setHelp('An Angular directive is a custom HTML tag implemented in Javascript.
Directives can be used in many ways -- to define new input elements, new
decorations, or chunks of reusable content.

You should generally include a prefix in the directive name.
For example, directives in Angular core use "ng-*", and
directives in CiviCRM core use "crm-*".

For more, see https://docs.angularjs.org/guide/directive');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    //// Figure out template data ////
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

    $ctx['tsDomain'] = (string) $attrs['key'];
    $ctx['angularModuleName'] = $input->getOption('am') ? $input->getOption('am') : $ctx['mainFile'];
    $ctx['dirNameCamel'] = $this->toCamel($input->getArgument('<directive-name>'));
    $ctx['dirNameHyp'] = $this->toHyphen($input->getArgument('<directive-name>'));

    if (preg_match('/^' . $ctx['angularModuleName'] . '[A-Z]/', $ctx['dirNameCamel'])) {
      $ctx['baseFileName'] = preg_replace('/^' . $ctx['angularModuleName'] . '/', '', $ctx['dirNameCamel']);
    }
    else {
      $output->writeln('<error>Warning: Directive name does not begin with module name.</error>');
      $ctx['baseFileName'] = $ctx['dirNameCamel'];
    }

    $ctx['jsPath'] = $basedir->string('ang', $ctx['angularModuleName'], $ctx['baseFileName'] . '.js');
    $ctx['htmlName'] = implode('/', ['~', $ctx['angularModuleName'], $ctx['baseFileName'] . '.html']);
    $ctx['htmlPath'] = $basedir->string('ang', $ctx['angularModuleName'], $ctx['baseFileName'] . '.html');

    //// Construct files ////
    $output->writeln("<info>Initialize Angular directive \"" . $ctx['dirNameHyp'] . "\" (aka \"" . $ctx['dirNameCamel'] . "\")</info>");

    $ext = new Collection();
    $ext->builders['dirs'] = new Dirs([
      dirname($ctx['jsPath']),
      dirname($ctx['htmlPath']),
    ]);;

    $ext->builders['js'] = new Template('angular-dir.js.php', $ctx['jsPath'], FALSE, Services::templating());
    $ext->builders['html'] = new Template('angular-dir.html.php', $ctx['htmlPath'], FALSE, Services::templating());

    $ext->init($ctx);
    $ext->save($ctx, $output);
  }

  public function toHyphen($dirName) {
    $buf = '';
    foreach (str_split($dirName) as $char) {
      if ($char >= 'A' && $char <= 'Z') {
        $buf .= '-' . strtolower($char);
      }
      else {
        $buf .= $char;
      }
    }
    return trim(preg_replace('/-+/', '-', $buf), '-');
  }

  public function toCamel($dirName) {
    return lcfirst(preg_replace('/ /', '', ucwords(preg_replace('/-/', ' ', $dirName))));
  }

}
