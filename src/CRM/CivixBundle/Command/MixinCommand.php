<?php

namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Mixins;
use Civix;
use CRM\CivixBundle\Utils\CivixStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Utils\Path;
use Symfony\Component\Console\Style\SymfonyStyle;

class MixinCommand extends AbstractCommand {

  protected function configure() {
    Civix::templating();
    $this
      ->setName('mixin')
      ->setDescription('Inspect and update list of mixins')
      ->addArgument('key', InputArgument::OPTIONAL, "Extension identifier (Ex: \"foo-bar\" or \"org.example.foo-bar\")")
      ->addOption('disable', NULL, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'List of mixins to disable')
      ->addOption('enable', NULL, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'List of mixins to enable')
      ->addOption('enable-all', NULL, InputOption::VALUE_NONE, 'Enable all known mixins')
      ->addOption('disable-all', NULL, InputOption::VALUE_NONE, 'Disable all known mixins')
      ->setHelp(
        "Inspect and update list of mixins\n" .
        "\n" .
        "<comment>Examples:</comment>\n" .
        "  civix mixin\n" .
        "  civix mixin --enable-all\n" .
        "  civix mixin --enable=ang-php@1,setting-php@1\n" .
        "  civix mixin --disable=ang-php@1\n" .
        "  civix mixin --disable-all --enable=mgd-php@1\n" .
        "\n"
      );
    parent::configure();
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->assertCurrentFormat();

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);
    $info = $this->getModuleInfo($ctx);

    $mixins = new Mixins($info, $basedir->string('mixin'));

    $change = FALSE;
    if ($input->getOption('disable-all')) {
      $change = TRUE;
      $this->disableAll($input, $output, $mixins);
    }
    if ($input->getOption('enable-all')) {
      $change = TRUE;
      $this->enableAll($input, $output, $mixins);
    }
    if ($input->getOption('disable')) {
      $change = TRUE;
      $this->disableSome($input, $output, $mixins);
    }
    if ($input->getOption('enable')) {
      $change = TRUE;
      $this->enableSome($input, $output, $mixins);
    }

    if ($change) {
      $mixins->save($ctx, $output);
      $info->save($ctx, $output);
    }
    else {
      $io = new CivixStyle($input, $output);
      $this->showList($io, $mixins);
    }

    return 0;
  }

  protected function enableAll(InputInterface $input, OutputInterface $output, Mixins $mixins) {
    $all = $this->findAllMixins();
    foreach ($all as $spec) {
      $mixins->addMixin($spec['mixinConstraint']);
    }
  }

  protected function disableAll(InputInterface $input, OutputInterface $output, Mixins $mixins): void {
    $mixins->removeAllMixins();
  }

  protected function enableSome(InputInterface $input, OutputInterface $output, Mixins $mixins) {
    $newMixins = $this->parseOptionList($input, 'enable');
    $this->validateNames($newMixins);
    foreach ($newMixins as $newMixin) {
      $mixins->addMixin($newMixin);
    }
  }

  protected function disableSome(InputInterface $input, OutputInterface $output, Mixins $mixins) {
    $removals = $this->parseOptionList($input, 'disable');
    foreach ($removals as $removal) {
      $mixins->removeMixin($removal);
    }
  }

  protected function showList(SymfonyStyle $io, Mixins $mixins) {
    $mixlib = Civix::mixlib();
    $mixinBackports = preg_grep(';@;', array_keys(Civix::mixinBackports()));

    $toNameMajor = function($mixinConstraint) {
      [$mixinName, $mixinVersion] = explode('@', $mixinConstraint);
      return $mixinName . '@' . explode('.', $mixinVersion)[0];
    };

    $nameMajors = array_unique(array_merge(
      array_map($toNameMajor, $mixins->getDeclaredMixinConstraints()),
      array_map($toNameMajor, $mixinBackports)
    ));
    sort($nameMajors);

    $data = [];
    foreach ($nameMajors as $nameMajor) {
      $data[$nameMajor] = ['name' => explode('@', $nameMajor)[0], 'status' => 'off', 'constraint' => '', 'available' => ''];
    }

    foreach ($mixins->getDeclaredMixinConstraints() as $mixinConstraint) {
      $nameMajor = $toNameMajor($mixinConstraint);
      [$mixinName, $mixinVersion] = explode('@', $mixinConstraint);
      $data[$nameMajor]['status'] = 'on';
      $data[$nameMajor]['constraint'] = $mixinVersion;
    }

    foreach ($mixinBackports as $availMixin) {
      $mixinInfo = $mixlib->get($availMixin);
      $nameMajor = $toNameMajor($mixinInfo['mixinConstraint']);
      $data[$nameMajor]['available'] = $mixinInfo['mixinVersion'];
    }

    $io->table(['name', 'status', 'constraint', 'available'], $data);
    $io->writeln('<comment>Note: The format of this command may change in the future.</comment>');
  }

  protected function validateNames(array $mixinNames): array {
    foreach ($mixinNames as $mixinName) {
      if (!preg_match('/^[-_a-zA-Z0-9]+@[\d\.]+$/', $mixinName)) {
        throw new \RuntimeException("Malformed or incomplete option \"$mixinName\". (Expected \"{name}@{version}\")");
      }
    }

    return $mixinNames;
  }

  protected function parseOptionList(InputInterface $input, string $optionName) {
    $optionValue = (array) $input->getOption($optionName);
    $requested = explode(',', implode(',', $optionValue));
    return array_filter(array_unique($requested));
  }

  protected function findAllMixins(): iterable {
    yield from [];
    $mixlib = Civix::mixlib();
    $mixinBackports = preg_grep(';@;', array_keys(Civix::mixinBackports()));
    sort($mixinBackports);
    foreach ($mixinBackports as $id) {
      yield $mixlib->get($id);
    }
  }

}
