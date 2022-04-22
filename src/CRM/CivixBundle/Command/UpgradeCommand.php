<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Services;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Utils\Path;

class UpgradeCommand extends AbstractCommand {

  protected function configure() {
    Services::templating();
    $this
      ->setName('upgrade')
      ->setDescription('Apply upgrades to the layout of the codebase');
    parent::configure();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $ctx = [];
    $ctx['type'] = 'module';

    // Refresh existing module
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);

    $info = $this->getModuleInfo($ctx);

    $module = new Module(Services::templating());
    $module->loadInit($ctx);
    $module->save($ctx, $output);

    // Don't do this as a blanket thing - leave it to specific upgrade steps.
    // $mixins = new Mixins($info, $basedir->string('mixin'), $this->getMixins($input));
    // $mixins->save($ctx, $output);

    $info->save($ctx, $output);
    return 0;
  }

}
