<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\CopyFile;
use CRM\CivixBundle\Builder\Mixins;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Services;
use CRM\CivixBundle\Utils\Naming;
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

  protected $defaultMixins = ['setting-php@1', 'mgd-php@1', 'smarty-v2@1'];

  protected function configure() {
    Services::templating();
    $this
      ->setName('generate:module')
      ->setDescription('Create a new CiviCRM Module-Extension (Regenerate module.civix.php if \"key\" not specified)')
      ->addArgument('key', InputArgument::OPTIONAL, "Extension identifier (Ex: \"foo_bar\" or \"org.example.foo-bar\")")
      ->addOption('enable', NULL, InputOption::VALUE_REQUIRED, 'Whether to auto-enable the new module (yes/no/ask)', 'ask')
      ->addOption('compatibility', NULL, InputOption::VALUE_REQUIRED, 'Version of CiviCRM that we target (eg "5.30" or "current")', 'current')
      ->addOption('mixins', NULL, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Automatically enable the listed mixins')
      ->addOption('license', NULL, InputOption::VALUE_OPTIONAL, 'License for the extension (' . implode(', ', $this->getLicenses()) . ')', $this->getDefaultLicense())
      ->addOption('author', NULL, InputOption::VALUE_REQUIRED, 'Name of the author', $this->getDefaultAuthor())
      ->addOption('email', NULL, InputOption::VALUE_OPTIONAL, 'Email of the author', $this->getDefaultEmail())
      ->setHelp(
        "Create a new CiviCRM Module-Extension (Regenerate module.civix.php if \"key\" is not specified)\n" .
        "\n" .
        "<comment>Identification:</comment>\n" .
        "  Keys should be lowercase alphanumeric with underscores. Dots and dashes may be used with caveats.\n" .
        "\n" .
        "  CiviCRM extensions formally have two names, the \"key\" and the \"file\".\n" .
        "  Some APIs use the \"key\" name, and other APIs use the \"file\" name.\n" .
        "  The \"key\" allows a Java-style prefix (reverse domain name), but \"file\" does not.\n" .
        "\n" .
        "  If you use a Java-style prefix (dots and dashes), then the extension will have split names.\n" .
        "\n" .
        "  If you omit a Java-style prefix (dots and dashes), then the extension will have a single (matching) name.\n" .
        "\n" .
        "<comment>Examples:</comment>\n" .
        "  civix generate:module foo_bar\n" .
        "  civix generate:module foo_bar --license=AGPL-3.0 --author=\"Alice\" --email=\"alice@example.org\"\n" .
        "  civix generate:module org.example.foo-bar \n" .
        "\n"
      );
    parent::configure();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    Services::boot(['output' => $output]);

    $ctx = [];
    $ctx['type'] = 'module';
    if (!$input->getArgument('key')) {
      throw new \RuntimeException('To update an existing extension, please use "civix upgrade"');
    }

    $licenses = new \LicenseData\Repository();

    $name = $input->getArgument('key');

    // Name should start with an alpha and only contain alphanumeric, - and .
    if (!Naming::isValidFullName($name)) {
      $output->writeln('<error>Malformed package name</error>');
      return 1;
    }

    $ctx['basedir'] = $name;
    $ctx['fullName'] = $name;
    $ctx['mainFile'] = Naming::createShortName($name);
    $ctx['namespace'] = 'CRM/' . Naming::createCamelName($name);
    $ctx['angularModuleName'] = 'crm' . Naming::createCamelName($name);

    if ($input->getOption('author') && $input->getOption('email')) {
      $ctx['author'] = $input->getOption('author');
      $ctx['email'] = $input->getOption('email');
    }
    else {
      $output->writeln("<error>Missing author name or email address</error>");
      $output->writeln("<error>Please pass --author and --email, or set defaults in ~/.gitconfig</error>");
      return 1;
    }
    $ctx['license'] = $input->getOption('license');
    if ($licenses->get($ctx['license'])) {
      $output->writeln(sprintf('<comment>License set to %s (authored by %s \<%s>)</comment>', $ctx['license'], $ctx['author'], $ctx['email']));
      $output->writeln('<comment>If this is in error, please correct info.xml and LICENSE.txt</comment>');
    }
    else {
      $output->writeln('<error>Unrecognized license (' . $ctx['license'] . ')</error>');
      return 1;
    }

    if ($input->getOption('compatibility') === 'current') {
      [$verMajor, $verMinor] = explode('.', \CRM_Utils_System::version());
      $ctx['compatibilityVerMin'] = "$verMajor.$verMinor";
    }
    else {
      $ctx['compatibilityVerMin'] = $input->getOption('compatibility');
    }

    if ($ctx['fullName'] !== $ctx['mainFile']) {
      $output->writeln("");
      $output->writeln("<error>ALERT:</error> <comment>The requested command requires split-naming.</comment>");
      $output->writeln("");
      $output->writeln("CiviCRM extensions formally have two names, the \"key\" and the \"file\".");
      $output->writeln("Some APIs use the \"key\" name, and other APIs use the \"file\" name.");
      $output->writeln("");
      $output->writeln("   <comment>\"Key\":</comment> Appears in many strings+indices. Allows Java-style prefix.");
      $output->writeln("         <comment>Requested Value:</comment> {$ctx['fullName']}");
      $output->writeln("         <comment>Example Usage:</comment> addStyleFile('{$ctx['fullName']}', 'example.css')");
      $output->writeln("   <comment>\"File\"</comment>: Appears in PHP files+functions. No Java-style prefix.");
      $output->writeln("         <comment>Requested Value:</comment> {$ctx['mainFile']}");
      $output->writeln("         <comment>Example Usage:</comment> function {$ctx['mainFile']}_civicrm_config() {}");
      $output->writeln("");
      $output->writeln("Many developers find it easier to use matching names, but the");
      $output->writeln("requested command requires splitting the names. You may continue with");
      $output->writeln("split names, or you may cancel and try again with a simpler name.");
      $output->writeln("");
      if (!$this->confirm($input, $output, "Continue with current (split) name? [Y/n] ")) {
        return 1;
      }
    }

    $ext = new Collection();

    $output->writeln("<info>Initalize module</info> " . $ctx['fullName']);

    $basedir = new Path($ctx['basedir']);

    $info = new Info($basedir->string('info.xml'));

    $ext->builders['dirs'] = new Dirs([
      $basedir->string('build'),
      $basedir->string('templates'),
      $basedir->string('xml'),
      $basedir->string('images'),
      $basedir->string($ctx['namespace']),
    ]);
    $ext->builders['mixins'] = new Mixins($info, $basedir->string('mixin'), $this->getMixins($input));
    $ext->builders['info'] = $info;
    $ext->builders['module'] = new Module(Services::templating());
    $ext->builders['license'] = new License($licenses->get($ctx['license']), $basedir->string('LICENSE.txt'), FALSE);
    $ext->builders['readme'] = new Template('readme.md.php', $basedir->string('README.md'), FALSE, Services::templating());
    $ext->builders['screenshot'] = new CopyFile(dirname(dirname(dirname(dirname(__DIR__)))) . '/images/placeholder.png', $basedir->string('images/screenshot.png'), FALSE);
    $ext->loadInit($ctx);
    $ext->save($ctx, $output);

    $this->tryEnable($input, $output, $ctx['fullName']);
    return 0;
  }

  /**
   * Attempt to enable the extension on the linked CiviCRM site
   *
   * @return bool TRUE on success; FALSE if there's no site or if there's an error
   */
  protected function tryEnable(InputInterface $input, OutputInterface $output, $key) {
    Services::boot(['output' => $output]);
    $civicrm_api3 = Services::api3();

    if ($civicrm_api3 && $civicrm_api3->local) {
      $siteName = \CRM_Utils_System::baseURL();

      $output->writeln("<info>Refresh extension list for</info> $siteName");
      if (!$civicrm_api3->Extension->refresh(['local' => TRUE, 'remote' => FALSE])) {
        $output->writeln("<error>Refresh error: " . $civicrm_api3->errorMsg() . "</error>");
        return FALSE;
      }
      if ($input->getOption('enable') === 'no') {
        return FALSE;
      }

      if ($input->getOption('enable') === 'yes' || $this->confirm($input, $output, "Enable extension ($key) in \"$siteName\"? [Y/n] ")) {
        $output->writeln("<info>Enable extension ($key) in</info> $siteName");
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

  protected function getMixins(InputInterface $input) {
    $requested = explode(',', implode(',', $input->getOption('mixins')));
    $merged = array_unique(array_merge($this->defaultMixins, $requested));
    return array_filter($merged);
  }

}
