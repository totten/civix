<?php
namespace CRM\CivixBundle\Command;

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

  protected function createClassName($ctx) {
    $namespace = str_replace('/', '_', $ctx['namespace']);
    return $namespace . '_Page_' . $ctx['shortClassName'];
  }

  protected function createTplName($ctx) {
    return $ctx['namespace'] . '/Page/' . $ctx['shortClassName'] . '.tpl';
  }
}
