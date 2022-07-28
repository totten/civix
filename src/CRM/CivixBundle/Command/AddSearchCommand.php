<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Mixins;
use CRM\CivixBundle\Services;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\CopyClass;
use CRM\CivixBundle\Builder\CopyFile;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\PhpData;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;

class AddSearchCommand extends AbstractCommand {
  const GENERIC_SEARCH_TEMPLATE = 'CRM/Contact/Form/Search/Custom.tpl';

  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:search')
      ->setDescription('Add a custom search to a module-extension')
      ->addArgument('<ClassName>', InputArgument::REQUIRED, 'Search class name (eg "MySearch")')
      ->addOption('copy', NULL, InputOption::VALUE_OPTIONAL, 'Full class name of an existing search which should be copied (eg "CRM_Contact_Form_Search_Custom_ZipCodeRange")');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->assertCurrentFormat();

    //// Figure out template data ////
    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);

    $info = $this->getModuleInfo($ctx);

    $ctx['searchClassName'] = strtr($ctx['namespace'], '/', '_') . '_Form_Search_' . $input->getArgument('<ClassName>');
    $ctx['searchClassFile'] = $basedir->string(strtr($ctx['searchClassName'], '_', '/') . '.php');
    $ctx['searchMgdFile'] = $basedir->string(strtr($ctx['searchClassName'], '_', '/') . '.mgd.php');
    $ctx['searchTplRelFile'] = strtr($ctx['searchClassName'], '_', '/') . '.tpl';
    $ctx['searchTplFile'] = $basedir->string('templates', $ctx['searchTplRelFile']);

    //// Construct files ////
    $output->writeln("<info>Initialize search</info> " . $ctx['searchClassName']);

    $ext = new Collection();
    $ext->builders['dirs'] = new Dirs([
      dirname($ctx['searchClassFile']),
      dirname($ctx['searchMgdFile']),
    ]);
    ;

    if (!file_exists($ctx['searchMgdFile'])) {
      $mgdEntities = [
        [
          'name' => $ctx['searchClassName'],
          'entity' => 'CustomSearch',
          'params' => [
            'version' => 3,
            'label' => $input->getArgument('<ClassName>'),
            'description' => sprintf("%s (%s)", $input->getArgument('<ClassName>'), $ctx['fullName']),
            'class_name' => $ctx['searchClassName'],
          ],
        ],
      ];
      $header = "// This file declares a managed database record of type \"CustomSearch\".\n"
        . "// The record will be automatically inserted, updated, or deleted from the\n"
        . "// database as appropriate. For more details, see \"hook_civicrm_managed\" at:\n"
        . "// https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed";
      $ext->builders['mgd.php'] = new PhpData($ctx['searchMgdFile'], $header);
      $ext->builders['mgd.php']->set($mgdEntities);
    }

    if ($srcClassName = $input->getOption('copy')) {
      // we need bootstrap to set up include path to locate file -- but that's it
      Services::boot(['output' => $output]);
      $civicrm_api3 = Services::api3();
      if (!$civicrm_api3 || !$civicrm_api3->local) {
        $output->writeln("<error>--copy requires access to local CiviCRM source tree. Configure civicrm_api3_conf_path.</error>");
        return;
      }

      if (self::findTpl($srcClassName) == self::GENERIC_SEARCH_TEMPLATE) {
        $ext->builders['search.php'] = new CopyClass($srcClassName, $ctx['searchClassName'], $ctx['searchClassFile'], FALSE);
      }
      else {
        $ext->builders['dirs']->paths[] = dirname($ctx['searchTplFile']);
        $origTplFile = self::findTpl($srcClassName);
        $ext->builders['search.php'] = new CopyClass($srcClassName, $ctx['searchClassName'], $ctx['searchClassFile'], FALSE,
          function ($phpSrc) use ($origTplFile, $ctx) {
            // i could wile away the hours
            // conferring with the flowers
            // consulting with the rain
            // if i only had a parser
            return strtr($phpSrc, [
              $origTplFile => $ctx['searchTplRelFile'],
            ]);
          }
        );
        $ext->builders['page.tpl.php'] = new CopyFile('templates/' . $origTplFile, $ctx['searchTplFile'], FALSE);
      }
    }
    else {
      $ext->builders['search.php'] = new Template('search.php.php', $ctx['searchClassFile'], FALSE, Services::templating());
      // $ext->builders['page.tpl.php'] = new Template('search.tpl.php', $ctx['searchTplFile'], FALSE, Services::templating());
    }

    $ext->builders['mixins'] = new Mixins($info, $basedir->string('mixin'), ['mgd-php@1.0']);
    $ext->builders['info'] = $info;

    $ext->loadInit($ctx);
    $ext->save($ctx, $output);
  }

  /**
   * Determine which template file correlates to the given controller
   *
   * @param string $srcClassName
   * @return string
   */
  protected static function findTpl($srcClassName) {
    $formValues = [];
    $search = new $srcClassName($formValues);
    return $search->templateFile();
  }

}
