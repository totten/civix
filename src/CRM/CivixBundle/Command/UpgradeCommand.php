<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Services;
use CRM\CivixBundle\Upgrader;
use CRM\CivixBundle\Utils\Naming;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Utils\Path;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpgradeCommand extends AbstractCommand {

  protected function configure() {
    Services::templating();
    $this
      ->setName('upgrade')
      ->setDescription('Apply upgrades to the layout of the codebase')
      ->addOption('start', NULL, InputOption::VALUE_REQUIRED, 'Replay the upgrade steps, starting from an older version', 'current')
      ->setHelp('
This command applies incremental upgrade steps, starting from the declared version
(info.xml\'s <civix><format>...</format></civix>) and proceeding to the current.

In some edge-cases (eg merging code-branches from different versions), you may find
it useful to replay upgrade steps. For example:

  civix upgrade --start=0
  civix upgrade --start=13.10.0
  civix upgrade --start=22.05.0

Most upgrade steps should be safe to re-run repeatedly, but this is not guaranteed.
');
    parent::configure();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $startVer = $input->getOption('start');
    if ($startVer !== 'current') {
      $verAliases = ['0' => '13.10.0'];
      $upgrader = new Upgrader($input, $output, new Path(\CRM\CivixBundle\Application::findExtDir()));
      $upgrader->updateFormatVersion($verAliases[$startVer] ?? $startVer);
    }

    $this->executeIncrementalUpgrades($input, $output);
    $this->executeGenericUpgrade($input, $output);
    return 0;
  }

  protected function executeIncrementalUpgrades(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $io->title('Incremental upgrades');

    [$ctx, $info] = $this->loadCtxInfo();
    $startVersion = $ctx['civixFormat'] ?? NULL;

    if (!$startVersion) {
      $startVersion = $info->detectFormat();
      $io->writeln("info.xml does not declare the civix format. Inferred <info>v{$startVersion}</info>.");
    }
    else {
      $io->writeln("Current civix format is <info>v{$startVersion}</info>.");
    }

    $upgrades = Services::upgradeList()->findUpgrades($startVersion);
    if (empty($upgrades)) {
      $io->writeln("No incremental upgrades required.");
      return 0;
    }

    $lastVersion = $startVersion;
    foreach ($upgrades as $upgradeVersion => $upgradeFile) {
      $io->section("Upgrade <info>v{$lastVersion}</info> => <info>v{$upgradeVersion}</info>");
      $io->writeln("<info>Executing upgrade script</info> $upgradeFile");

      $upgrader = new Upgrader($input, $output, new Path(\CRM\CivixBundle\Application::findExtDir()));
      $func = require $upgradeFile;
      $func($upgrader);
      $upgrader->updateFormatVersion($upgradeVersion);
      $lastVersion = $upgradeVersion;
    }
  }

  protected function executeGenericUpgrade(InputInterface $input, OutputInterface $output): void {
    $io = new SymfonyStyle($input, $output);
    $io->title('General upgrade');

    $upgrader = new Upgrader($input, $output, new Path(\CRM\CivixBundle\Application::findExtDir()));
    $upgrader->cleanEmptyHooks();
    $upgrader->reconcileMixins();

    /**
     * @var \CRM\CivixBundle\Builder\Info $info
     */
    [$ctx, $info] = $this->loadCtxInfo();
    $basedir = new Path(\CRM\CivixBundle\Application::findExtDir());

    $module = new Module(Services::templating());
    $module->loadInit($ctx);
    $module->save($ctx, $output);

    if ($ctx['namespace']) {
      $phpFile = $basedir->string(Naming::createClassFile($ctx['namespace'], 'Upgrader', 'Base.php'));
      if (file_exists($phpFile)) {
        $output->writeln(sprintf('<info>Write</info> %s', $phpFile));
        file_put_contents($phpFile, Services::templating()->render('upgrader-base.php.php', $ctx));
      }
    }
  }

  protected function loadCtxInfo(): array {
    $ctx = [];
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $ctx['type'] = 'module';
    $info = $this->getModuleInfo($ctx);
    return [$ctx, $info];
  }

}
