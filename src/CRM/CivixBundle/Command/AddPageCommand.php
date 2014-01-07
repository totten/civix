<?php
namespace CRM\CivixBundle\Command;
use Symfony\Component\Console\Input\InputInterface;

class AddPageCommand extends AbstractAddPageCommand {
  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:page')
      ->setDescription('Add a basic web page to a CiviCRM Module-Extension');
  }

  protected function getPhpTemplate(InputInterface $input) {
    return 'CRMCivixBundle:Code:page.php.php';
  }

  protected function getTplTemplate(InputInterface $input) {
    return 'CRMCivixBundle:Code:page.tpl.php';
  }

  protected function createClassName(InputInterface $input, $ctx) {
    $namespace = str_replace('/', '_', $ctx['namespace']);
    return $namespace . '_Page_' . $ctx['shortClassName'];
  }

  protected function createTplName(InputInterface $input, $ctx) {
    return $ctx['namespace'] . '/Page/' . $ctx['shortClassName'] . '.tpl';
  }
}
