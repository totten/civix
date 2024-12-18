<?php
namespace CRM\CivixBundle\Command;

use Civix;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PingCommand extends AbstractCommand {

  protected function configure() {
    parent::configure();
    $this
      ->setName('civicrm:ping')
      ->setDescription('Test whether the CiviCRM client is properly configured');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    Civix::boot(['output' => $output]);
    $civicrm_api3 = Civix::api3();
    if ($civicrm_api3->Contact->Get(['option.limit' => 1])) {
      if (empty($civicrm_api3->result->values[0]->contact_type)) {
        $output->writeln('<error>Ping failed: Site reported that it found no contacts</error>');
      }
      else {
        $output->writeln('<info>Ping successful</info>');
      }
    }
    else {
      $output->writeln('<error>Ping failed: API Error: ' . $civicrm_api3->errorMsg() . '</error>');
    }
    return 0;
  }

}
