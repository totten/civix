<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Mixins;
use CRM\CivixBundle\Services;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Utils\Path;
use Exception;

class AddCaseTypeCommand extends AbstractCommand {

  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:case-type')
      ->setDescription('Add a CiviCase case-type')
      ->addArgument('<Label>', InputArgument::REQUIRED, 'Printable name of the case type')
      ->addArgument('<Name>', InputArgument::OPTIONAL, 'Code name of the case type (Default: Derive from <Label>)');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    // load Civi to get access to civicrm_api_get_function_name
    Services::boot(['output' => $output]);
    $civicrm_api3 = Services::api3();
    if (!$civicrm_api3 || !$civicrm_api3->local) {
      $output->writeln("Requires access to local CiviCRM source tree. Configure civicrm_api3_conf_path.</error>");
      return;
    }

    if (!preg_match('/^[A-Z][A-Za-z0-9_ \.\-]*$/', $input->getArgument('<Label>'))) {
      throw new Exception("Label should be valid");
    }
    if (!$input->getArgument('<Name>')) {
      // $input->setArgument('<Name>', \CRM_Utils_String::munge(ucwords(str_replace('_', ' ', $input->getArgument('<Label>'))), '', 0));
      $input->setArgument('<Name>', \CRM_Case_XMLProcessor::mungeCasetype($input->getArgument('<Label>')));
    }
    if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $input->getArgument('<Name>'))) {
      throw new Exception("Name should be valid (alphanumeric beginning with uppercase)");
    }

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $ctx['caseTypeLabel'] = $input->getArgument('<Label>');
    $ctx['caseTypeName'] = $input->getArgument('<Name>');

    $basedir = new Path($ctx['basedir']);

    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);
    $attrs = $info->get()->attributes();
    if ($attrs['type'] != 'module') {
      $output->writeln('<error>Wrong extension type: ' . $attrs['type'] . '</error>');
      return;
    }

    $dirs = new Dirs([
      $basedir->string('xml', 'case'),
    ]);
    $dirs->save($ctx, $output);

    $xmlFile = $basedir->string('xml', 'case', $ctx['caseTypeName'] . '.xml');
    if (!file_exists($xmlFile)) {
      $output->writeln(sprintf('<info>Write %s</info>', $xmlFile));
      file_put_contents($xmlFile, Services::templating()
        ->render('case-type.xml.php', $ctx));
    }
    else {
      $output->writeln(sprintf('<error>Skip %s: file already exists</error>', $xmlFile));
    }

    $module = new Module(Services::templating());
    $module->loadInit($ctx);
    $module->save($ctx, $output);

    $mixins = new Mixins($info, $basedir->string('mixin'), ['case-xml@1.0']);
    $mixins->save($ctx, $output);
    $info->save($ctx, $output);
  }

}
