<?php
namespace CRM\CivixBundle\Command;

use Civix;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddUpgraderCommand extends AbstractCommand {

  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:upgrader')
      ->setDescription('Add an example upgrader class to a CiviCRM Module-Extension');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->assertCurrentFormat();
    if (!\Civix::checker()->hasUpgrader()) {
      Civix::generator()->addUpgrader();
    }
    else {
      // TODO: Realign existence-check with Generator::addClass().
      $output->writeln("<comment>Upgrader already exists</comment>");
    }
    return 0;
  }

}
