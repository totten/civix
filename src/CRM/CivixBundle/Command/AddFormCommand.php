<?php
namespace CRM\CivixBundle\Command;

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

  protected function createClassName($ctx) {
    $namespace = str_replace('/', '_', $ctx['namespace']);
    return $namespace . '_Form_' . $ctx['shortClassName'];
  }

  protected function createTplName($ctx) {
    return $ctx['namespace'] . '/Form/' . $ctx['shortClassName'] . '.tpl';
  }
}
