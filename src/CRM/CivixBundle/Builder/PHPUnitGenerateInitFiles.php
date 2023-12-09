<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Services;
use CRM\CivixBundle\Utils\Files;
use Symfony\Component\Console\Output\OutputInterface;

class PHPUnitGenerateInitFiles {

  /**
   * @param $bootstrapFile
   * @param $ctx
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param $renderTemplateName
   */
  public function initPhpunitBootstrap($bootstrapFile, &$ctx, OutputInterface $output) {
    if (!file_exists($bootstrapFile)) {
      $dirs = new Dirs([
        dirname($bootstrapFile),
      ]);
      $dirs->save($ctx, $output);

      $output->writeln(sprintf('<info>Write</info> %s', Files::relativize($bootstrapFile)));
      file_put_contents($bootstrapFile, Services::templating()
        ->render('phpunit-boot-cv.php.php', $ctx));
    }
    else {
      $output->writeln(sprintf('<comment>Skip %s: file already exists</comment>', Files::relativize($bootstrapFile)));
    }
  }

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
