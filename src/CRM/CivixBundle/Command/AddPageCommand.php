<?php
namespace CRM\CivixBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Dirs;

class AddPageCommand extends AbstractAddPageCommand {
  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:page')
      ->setDescription('Add a basic web page to a CiviCRM Module-Extension');
  }

  protected function getPhpTemplate() {
    return 'CRMCivixBundle:Code:page.php.php';
  }

  protected function getTplTemplate() {
    return 'CRMCivixBundle:Code:page.tpl.php';
  }

  protected function createClassName($ctx, $input) {
    $namespace = str_replace('/', '_', $ctx['namespace']);
    return $namespace . '_Page_' . $input->getArgument('<ClassName>');
  }

  protected function createTplName($ctx) {
    return $ctx['namespace'] . '/Page/' . $ctx['pageClassName'] . '.tpl';
  }
}
