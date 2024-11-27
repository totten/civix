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

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->assertCurrentFormat();
    Civix::generator()->addUpgrader('if-forced');
    return 0;
  }

}
