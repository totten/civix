<?php
namespace CRM\CivixBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\License;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Utils\Path;

class InitCommand extends AbstractCommand {
  protected function configure() {
    $this
      ->setName('generate:module')
      ->setDescription('Create a new CiviCRM Module-Extension')
      ->addArgument('<full.ext.name>', InputArgument::REQUIRED, 'Fully qualified extension name (e.g. "com.example.myextension")')
    //->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Type of extension (e.g. "module", "payment", "report", "search")', 'module')
      ->addOption('license', NULL, InputOption::VALUE_OPTIONAL, 'License for the extension (' . implode(', ', $this->getLicenses()) . ')', 'AGPL-3.0')
      ->addOption('author', NULL, InputOption::VALUE_REQUIRED, 'Name of the author', $this->getGitConfig('user.name', 'FIXME'))
      ->addOption('email', NULL, InputOption::VALUE_OPTIONAL, 'Email of the author', $this->getGitConfig('user.email', 'FIXME'));
    parent::configure();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $licenses = new \LicenseData\Repository();

    $ctx = array();
    $ctx['type'] = 'module';
    $ctx['fullName'] = $input->getArgument('<full.ext.name>');
    $ctx['basedir'] = $ctx['fullName'];
    if (preg_match('/^[a-z0-9\.]+\.([a-z0-9]+)$/', $ctx['fullName'], $matches)) {
      $ctx['mainFile'] = $matches[1];
      $ctx['namespace'] = 'CRM/' . strtoupper($ctx['mainFile']{0}) . substr($ctx['mainFile'], 1);
    }
    else {
      $output->writeln('<error>Malformed package name</error>');
      return;
    }
    if ($input->getOption('author') && $input->getOption('email')) {
      $ctx['author'] = $input->getOption('author');
      $ctx['email'] = $input->getOption('email');
    }
    else {
      $output->writeln("<error>Missing author name or email address</error>");
      $output->writeln("<error>Please pass --author and --email, or set defaults in ~/.gitconfig</error>");
      return;
    }
    $ctx['license'] = $input->getOption('license');
    if ($licenses->get($ctx['license'])) {
      $output->writeln(sprintf('<comment>License set to %s (authored by %s \<%s>)</comment>', $ctx['license'], $ctx['author'], $ctx['email']));
      $output->writeln('<comment>If this is in error, please correct info.xml and LICENSE.txt</comment>');
    }
    else {
      $output->writeln('<error>Unrecognized license (' . $ctx['license'] . ')</error>');
      return;
    }
    $ext = new Collection();

    $output->writeln("<info>Initalize module " . $ctx['fullName'] . "</info>");
    $basedir = new Path($ctx['basedir']);
    $ext->builders['dirs'] = new Dirs(array(
      $basedir->string('build'),
      $basedir->string('templates'),
      $basedir->string('xml'),
      $basedir->string($ctx['namespace']),
    ));
    $ext->builders['info'] = new Info($basedir->string('info.xml'));
    $ext->builders['module'] = new Module($this->getContainer()->get('templating'));
    $ext->builders['license'] = new License($licenses->get($ctx['license']), $basedir->string('LICENSE.txt'), FALSE);

    $ext->loadInit($ctx);
    $ext->save($ctx, $output);

    $this->tryEnable($input, $output, $ctx['fullName']);
  }

  /**
   * Attempt to enable the extension on the linked CiviCRM site
   *
   * @return bool TRUE on success; FALSE if there's no site or if there's an error
   */
  protected function tryEnable(InputInterface $input, OutputInterface $output, $key) {
    $civicrm_api3 = $this->getContainer()->get('civicrm_api3');
    if ($civicrm_api3 && $civicrm_api3->local) {
      $siteName = $civicrm_api3->getSiteName();
      /*
      The commented code doesn't work because installation requires a fully-bootstrapped
      system, but civicrm_api3 doesn't bootstrap the CMS.

      if ($this->confirm($input, $output, "Enable extension ($key) in $siteName? [Y/n] ")) {
          if (version_compare(\CRM_Utils_System::version(), '4.3.dev', '>=')) {
              if (! $civicrm_api3->Extension->refresh(array())) {
                  $output->writeln("<error>Refresh error: " . $civicrm_api3->errorMsg() . "</error>");
                  return FALSE;
              }
          }

          if (! $civicrm_api3->Extension->install(array('key' => $key))) {
                  $output->writeln("<error>Install error: " . $civicrm_api3->errorMsg() . "</error>");
          }
      }
      return TRUE;
      */
    }

    // fallback
    $output->writeln("NOTE: This might be a good time to refresh the extension list and install \"$key\".");
    return FALSE;
  }

  protected function getLicenses() {
    $licenses = new \LicenseData\Repository();
    return array_keys($licenses->getAll());
  }

  protected function getGitConfig($key, $default) {
    $result = NULL;
    if (\CRM\CivixBundle\Utils\Commands::findExecutable('git')) {
      $result = trim(`git config --get $key`);
    }
    if (empty($result)) {
      $result = $default;
    }
    return $result;
  }
}
