<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Services;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Builder\PhpData;
use CRM\CivixBundle\Utils\Path;
use Exception;

class AddThemeCommand extends Command {

  protected function configure() {
    $this
      ->setName('generate:theme')
      ->setDescription('Add a new theme to a CiviCRM Module-Extension (EXPERIMENTAL)')
      ->addArgument('name', InputArgument::OPTIONAL, 'The name of the theme')
      ->setHelp('Add a new theme to a CiviCRM Module-Extension

EXPERIMENTAL: At time of writing, this relies on a hook that has not yet
been released in a stable version of CiviCRM. The final hook should go out
on or after 5.8.x, but it is subject to change and approval.

Example: Generate an eponymous theme directly in the extension
$ civix generate:theme

Example: Generate a theme "foobar" in a subdirectory of the extension
$ civix generate:theme foobar
');
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

    if (!$input->getArgument('name')) {
      // Make an eponymous theme directly in the extension.
      $ctx['themeName'] = $ctx['mainFile'];
      $ctx['themePrefix'] = NULL;
      $ctx['themePrefixDir'] = $basedir;
    }
    else {
      // Make a named theme in a subdir.
      $ctx['themeName'] = $input->getArgument('name');
      $ctx['themePrefix'] = $ctx['themeName'] . '/';
      $ctx['themePrefixDir'] = $basedir->path($ctx['themeName']);
    }

    $ctx['themeMetaFile'] = $basedir->string($ctx['themeName'] . '.theme.php');
    $ctx['themeCivicrmCss'] = $ctx['themePrefixDir']->string('css', 'civicrm.css');
    $ctx['themeBootstrapCss'] = $ctx['themePrefixDir']->string('css', 'bootstrap.css');

    //// Construct files ////
    $output->writeln("<info>Initialize theme \"" . $ctx['themeName'] . "\"</info>");

    $ext = new Collection();
    $ext->builders['dirs'] = new Dirs([
      dirname($ctx['themeMetaFile']),
      dirname($ctx['themeCivicrmCss']),
      dirname($ctx['themeBootstrapCss']),
    ]);;

    if (!file_exists($ctx['themeMetaFile'])) {
      $header = "// This file declares a CSS theme for CiviCRM.\n";
      // TODO: Add hyperlink for docs.
      $themeMeta = [
        'name' => $ctx['themeName'],
        'title' => sprintf("%s theme", $ctx['themeName']),
        'prefix' => $ctx['themePrefix'],
        'url_callback' => '\Civi\Core\Themes\Resolvers::simple',
        'search_order' => array($ctx['themeName'], '_fallback_'),
        'excludes' => [],
      ];
      $ext->builders['theme.php'] = new PhpData($ctx['themeMetaFile'], $header);
      $ext->builders['theme.php']->set($themeMeta);
    }

    $ext->builders['civicrm.css'] = new Template('civicrm.css.php', $ctx['themeCivicrmCss'], FALSE, Services::templating());
    $ext->builders['bootstrap.css'] = new Template('bootstrap.css.php', $ctx['themeBootstrapCss'], FALSE, Services::templating());

    $ext->init($ctx);
    $ext->save($ctx, $output);
  }

}
