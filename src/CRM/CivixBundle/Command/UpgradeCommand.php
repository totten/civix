<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Services;
use CRM\CivixBundle\Upgrader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Utils\Path;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpgradeCommand extends AbstractCommand {

  protected function configure() {
    Services::templating();
    $this
      ->setName('upgrade')
      ->setDescription('Apply upgrades to the layout of the codebase');
    parent::configure();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
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
      // We'll have to make an educated guess...
      $mixins = $info->get()->xpath('mixins');
      $startVersion = empty($mixins) ? '13.10.0' : '22.04.0';
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

      $upgrader->updateInfo(function (Info $info) use ($upgradeVersion, $io) {
        $io->writeln("<info>Set civix format to </info>$upgradeVersion<info> in </info>info.xml");
        $info->get()->civix->format = $upgradeVersion;
      });
      $lastVersion = $upgradeVersion;
    }
  }

  protected function executeGenericUpgrade(InputInterface $input, OutputInterface $output): void {
    $io = new SymfonyStyle($input, $output);
    $io->title('General upgrade');

    [$ctx, $info] = $this->loadCtxInfo();

    $module = new Module(Services::templating());
    $module->loadInit($ctx);
    $module->save($ctx, $output);

    // Don't do this as a blanket thing - leave it to specific upgrade steps.
    // $mixins = new Mixins($info, $basedir->string('mixin'), $this->getMixins($input));
    // $mixins->save($ctx, $output);

    $info->save($ctx, $output);
  }

  protected function loadCtxInfo(): array {
    $ctx = [];
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $ctx['type'] = 'module';
    $info = $this->getModuleInfo($ctx);
    return [$ctx, $info];
  }

}
