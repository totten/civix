<?php
namespace CRM\CivixBundle\Command;

use Civix;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;

/**
 * @deprecated
 */
class AddAngularPageCommand extends AbstractCommand {

  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:angular-page')
      ->setDescription('Add a new Angular page (DEPRECATED)')
      ->addOption('am', NULL, InputOption::VALUE_REQUIRED, 'Name of the Angular module (default: match the Civi module name)')
      ->addArgument('<Ctrl>', InputArgument::REQUIRED, 'Controller name (Ex: "EditCtrl")')
      ->addArgument('<RelPath>', InputArgument::REQUIRED, 'Web path (Ex: "about/me")')
      ->setHelp('CiviCRM defines a base-page, "civicrm/a", and all Angular pages
are loaded underneath it. For example, if a page the path
"about/me", then the URL would be "civicrm/a/#/about/me".

Before generating an angular page, you\'ll need to generate a module using:
  civix generate:angular-module

To add a new Angular-absed page, this command autogenerates three things:
 * A route (JS)
 * A controller (JS)
 * A view (HTML)

For more, see https://docs.angularjs.org/guide');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->assertCurrentFormat();

    //// Figure out template data ////
    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);

    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);
    $attrs = $info->get()->attributes();

    $ctx['tsDomain'] = (string) $attrs['key'];
    $ctx['angularModuleName'] = $input->getOption('am') ? $input->getOption('am') : $ctx['angularModuleName'];
    $ctx['ctrlSuffix'] = $input->getArgument('<Ctrl>');
    $ctx['ctrlRelPath'] = $input->getArgument('<RelPath>');
    $ctx['ctrlName'] = ucwords($ctx['angularModuleName']) . $ctx['ctrlSuffix'];
    $ctx['jsPath'] = $basedir->string('ang', $ctx['angularModuleName'], $ctx['ctrlSuffix'] . '.js');
    $ctx['htmlName'] = implode('/', ['~', $ctx['angularModuleName'], $ctx['ctrlSuffix'] . '.html']);
    $ctx['htmlPath'] = $basedir->string('ang', $ctx['angularModuleName'], $ctx['ctrlSuffix'] . '.html');
    $ctx['hlpName'] = 'CRM' . '/' . $ctx['angularModuleName'] . '/' . $ctx['ctrlSuffix'];
    $ctx['hlpPath'] = $basedir->string('templates', $ctx['hlpName'] . '.hlp');

    //// Construct files ////
    $output->writeln("<info>Initialize Angular page</info> " . $ctx['ctrlName'] . " <info>(</info>civicrm/a/#/" . $ctx['ctrlRelPath'] . "<info>)</info>");

    $ext = new Collection();
    $ext->builders['dirs'] = new Dirs([
      dirname($ctx['jsPath']),
      dirname($ctx['htmlPath']),
      dirname($ctx['hlpPath']),
    ]);;

    $ext->builders['ctrl.js'] = new Template('angular-page.js.php', $ctx['jsPath'], FALSE, Civix::templating());
    $ext->builders['html'] = new Template('angular-page.html.php', $ctx['htmlPath'], FALSE, Civix::templating());
    $ext->builders['hlp'] = new Template('angular-page.hlp.php', $ctx['hlpPath'], FALSE, Civix::templating());

    $ext->init($ctx);
    $ext->save($ctx, $output);
    return 0;
  }

}
