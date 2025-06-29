<?php
namespace CRM\CivixBundle\Builder;

use Civix;
use CRM\CivixBundle\Utils\Files;
use Symfony\Component\Console\Output\OutputInterface;

class PHPUnitGenerateInitFiles {

  /**
   * @param $phpunitXmlFile
   * @param $ctx
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  public function initPhpunitXml($phpunitXmlFile, &$ctx, OutputInterface $output) {
    if (!file_exists($phpunitXmlFile)) {
      $phpunitXml = new PhpUnitXML($phpunitXmlFile);
      $phpunitXml->init($ctx);
      $phpunitXml->save($ctx, $output);
    }
    else {
      $output->writeln(sprintf('<comment>Skip %s: file already exists</comment>', Files::relativize($phpunitXmlFile)));
    }
  }

}
