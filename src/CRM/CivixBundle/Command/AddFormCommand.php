<?php
namespace CRM\CivixBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Dirs;

class AddFormCommand extends AbstractAddPageCommand {
  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:form')
      ->setDescription('Add a basic web form to a CiviCRM Module-Extension');
  }

  protected function getPhpTemplate() {
    return 'CRMCivixBundle:Code:form.php.php';
  }

  protected function getTplTemplate() {
    return 'CRMCivixBundle:Code:form.tpl.php';
  }

  protected function createClassName($ctx, $input) {
    $namespace = str_replace('/', '_', $ctx['namespace']);
    return $namespace . '_Form_' . $input->getArgument('<ClassName>');
  }

  protected function createTplName($ctx) {
    return $ctx['namespace'] . '/Form/' . $ctx['pageClassName'] . '.tpl';
  }
}
