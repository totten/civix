<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\CopyFile;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Services;
use CRM\CivixBundle\Utils\Naming;
use Symfony\Component\Console\Application;
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
    Services::templating();
    $this
      ->setName('generate:module')
      ->setDescription('Create a new CiviCRM Module-Extension (Regenerate module.civix.php if ext.name not specified)')
      ->addArgument('key', InputArgument::OPTIONAL, "Extension identifier (Ex: \"foo-bar\" or \"org.example.foo-bar\")")
      ->addOption('enable', NULL, InputOption::VALUE_REQUIRED, 'Whether to auto-enable the new module (yes/no/ask)', 'ask')
      ->addOption('license', NULL, InputOption::VALUE_OPTIONAL, 'License for the extension (' . implode(', ', $this->getLicenses()) . ')', $this->getDefaultLicense())
      ->addOption('author', NULL, InputOption::VALUE_REQUIRED, 'Name of the author', $this->getDefaultAuthor())
      ->addOption('email', NULL, InputOption::VALUE_OPTIONAL, 'Email of the author', $this->getDefaultEmail())
      ->setHelp(
        "Create a new CiviCRM Module-Extension (Regenerate module.civix.php if ext.name not specified)\n" .
        "\n" .
        "<comment>Identification:</comment>\n" .
        "  Keys must be lowercase alphanumeric (with dashes allowed).\n" .
        "\n" .
        "  Optionally, you may use a Java-style prefix (reverse domain name).\n" .
        "\n" .
        "  However, the prefix is mostly cosmetic. The base part of the key should be globally unique.\n" .
        "\n" .
        "<comment>Examples:</comment>\n" .
        "  civix generate:module foo-bar\n" .
        "  civix generate:module foo-bar --license=AGPL-3.0 --author=\"Alice\" --email=\"alice@example.org\"\n" .
        "  civix generate:module org.example.foo-bar \n" .
        "\n"
      );
    parent::configure();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $ctx = [];
    $ctx['type'] = 'module';
    if (!$input->getArgument('key')) {
      // Refresh existing module
      $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
      $basedir = new Path($ctx['basedir']);

      $info = new Info($basedir->string('info.xml'));
      $info->load($ctx);
      $attrs = $info->get()->attributes();
      if ($attrs['type'] != 'module') {
        $output->writeln('<error>Wrong extension type: ' . $attrs['type'] . '</error>');
        return;
      }

      $module = new Module(Services::templating());
      $module->loadInit($ctx);
      $module->save($ctx, $output);
      return;
    }

    $licenses = new \LicenseData\Repository();

    $name = $input->getArgument('key');

    // Name should start with an alpha and only contain alphanumeric, - and .
    if (!Naming::isValidFullName($name)) {
      $output->writeln('<error>Malformed package name</error>');
      return;
    }

    $ctx['basedir'] = $name;
    $ctx['fullName'] = $name;
    $ctx['mainFile'] = Naming::createShortName($name);
    $ctx['namespace'] = 'CRM/' . Naming::createCamelName($name);

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
    $ext->builders['dirs'] = new Dirs([
      $basedir->string('build'),
      $basedir->string('docs'),
      $basedir->string('templates'),
      $basedir->string('xml'),
      $basedir->string('images'),
      $basedir->string($ctx['namespace']),
    ]);
    $ext->builders['info'] = new Info($basedir->string('info.xml'));
    $ext->builders['module'] = new Module(Services::templating());
    $ext->builders['license'] = new License($licenses->get($ctx['license']), $basedir->string('LICENSE.txt'), FALSE);
    $ext->builders['readme'] = new Template('readme.md.php', $basedir->string('README.md'), FALSE, Services::templating());
    $ext->builders['mkdocs'] = new Template('mkdocs.yml.php', $basedir->string('mkdocs.yml'), FALSE, Services::templating());
    $ext->builders['docindex'] = new Template('index.md.php', $basedir->string('docs/index.md'), FALSE, Services::templating());
    $ext->builders['screenshot'] = new CopyFile(dirname(dirname(dirname(dirname(__DIR__)))) . '/images/placeholder.png', $basedir->string('images/screenshot.png'), FALSE);
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
    Services::boot(['output' => $output]);
    $civicrm_api3 = Services::api3();

    if ($civicrm_api3 && $civicrm_api3->local && version_compare(\CRM_Utils_System::version(), '4.3.dev', '>=')) {
      $siteName = \CRM_Utils_System::baseURL(); // \CRM_Core_Config::singleton()->userSystem->cmsRootPath();

      $output->writeln("<info>Refresh extension list for \"$siteName\"</info>");
      if (!$civicrm_api3->Extension->refresh(['local' => TRUE, 'remote' => FALSE])) {
        $output->writeln("<error>Refresh error: " . $civicrm_api3->errorMsg() . "</error>");
        return FALSE;
      }
      if ($input->getOption('enable') === 'no') {
        return FALSE;
      }

      if ($input->getOption('enable') === 'yes' || $this->confirm($input, $output, "Enable extension ($key) in \"$siteName\"? [Y/n] ")) {
        $output->writeln("<info>Enable extension ($key) in \"$siteName\"</info>");
        if (!$civicrm_api3->Extension->install(['key' => $key])) {
          $output->writeln("<error>Install error: " . $civicrm_api3->errorMsg() . "</error>");
        }
      }
      return TRUE;
    }

    // fallback
    $output->writeln("NOTE: This might be a good time to refresh the extension list and install \"$key\".");
    return FALSE;
  }

  protected function getDefaultLicense() {
    $config = Services::config();
    $license = NULL;
    if (!empty($config['parameters']['license'])) {
      $license = $config['parameters']['license'];
    }
    return empty($license) ? 'AGPL-3.0' : $license;
  }

  protected function getDefaultEmail() {
    $config = Services::config();
    $value = NULL;
    if (!empty($config['parameters']['email'])) {
      $value = $config['parameters']['email'];
    }
    return empty($value) ? $this->getGitConfig('user.email', 'FIXME') : $value;
  }

  protected function getDefaultAuthor() {
    $config = Services::config();
    $value = NULL;
    if (!empty($config['parameters']['author'])) {
      $value = $config['parameters']['author'];
    }
    return empty($value) ? $this->getGitConfig('user.name', 'FIXME') : $value;
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
