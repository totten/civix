<?php
namespace CRM\CivixBundle\Command;

use Symfony\Component\Console\Input\InputInterface;

class AddFormCommand extends AbstractAddPageCommand {

  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:form')
      ->setDescription('Add a basic web form to a CiviCRM Module-Extension');
  }

  protected function getPhpTemplate(InputInterface $input) {
    return 'form.php.php';
  }

  protected function getTplTemplate(InputInterface $input) {
    return 'form.tpl.php';
  }

  protected function createClassName(InputInterface $input, $ctx) {
    $namespace = str_replace('/', '_', $ctx['namespace']);
    return $namespace . '_Form_' . $ctx['shortClassName'];
  }

  protected function createTplName(InputInterface $input, $ctx) {
    return $ctx['namespace'] . '/Form/' . str_replace('_', '/', $ctx['shortClassName']) . '.tpl';
  }

}
