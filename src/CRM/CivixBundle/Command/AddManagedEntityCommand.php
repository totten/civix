<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Content;
use CRM\CivixBundle\Builder\Mixins;
use CRM\CivixBundle\Services;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\PhpData;
use CRM\CivixBundle\Utils\Path;

class AddManagedEntityCommand extends AbstractCommand {

  /**
   * Fields that most probably should be wrapped in E::ts()
   * @var array
   */
  private $localizable = ['title', 'label', 'description', 'text'];

  protected function configure() {
    parent::configure();
    $this
      ->setName('export')
      ->setDescription('(Experimental) Exports a record in packaged format for distribution in this extension')
      ->addArgument('<EntityName>', InputArgument::REQUIRED, 'API entity name (Ex: "SavedSearch")')
      ->addArgument('<EntityId>', InputArgument::REQUIRED, 'Id of entity to be exported (or name if exporting an Afform)')
      ->setHelp('Uses APIv4 Export to save existing records as .mgd.php files.
Specify the name of the entity and the id.
The file will be saved to the managed directory.

This command also works to export Afforms to the ang directory.

The command has some support for updating (re-exporting) managed records.
However, this is experimental. At time of writing, it does not interoperate
with most existing extensions+generators.
');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->assertCurrentFormat();

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $info = $this->getModuleInfo($ctx);

    $ext = new Collection();
    $ext->builders['dirs'] = new Dirs();

    $entityName = $input->getArgument('<EntityName>');
    $entityId = $input->getArgument('<EntityId>');

    // Boot CiviCRM to use api4
    Services::boot(['output' => $output]);

    try {
      if ($entityName === 'Afform') {
        $this->exportAfform($entityId, $info, $ext, $ctx);
      }
      else {
        $this->exportMgd($entityName, $entityId, $info, $ext, $ctx);
      }
    }
    catch (\Exception $e) {
      $output->writeln("Error: " . $e->getMessage());
      return 1;
    }

    $ext->builders['info'] = $info;

    $ext->loadInit($ctx);
    $ext->save($ctx, $output);
    return 0;
  }

  private function exportMgd($entityName, $id, $info, $ext, $ctx) {
    $basedir = new Path($ctx['basedir']);
    $ext->builders['mixins'] = new Mixins($info, $basedir->string('mixin'), ['mgd-php@1.0']);
    $ext->builders['dirs']->addPath($basedir->string('managed'));

    $export = (array) \civicrm_api4($entityName, 'export', [
      'checkPermissions' => FALSE,
      'id' => $id,
    ]);
    if (!$export) {
      throw new \Exception("$entityName $id not found.");
    }
    $managedName = $export[0]['name'];

    $localizable = $this->localizable;
    // Lookup entity-specific fields that should be wrapped in E::ts()
    foreach ($export as $item) {
      $fields = (array) \civicrm_api4($item['entity'], 'getFields', [
        'checkPermissions' => FALSE,
        'where' => [['localizable', '=', TRUE]],
      ], ['name']);
      $localizable = array_merge($localizable, $fields);
    }

    $managedFileName = $basedir->string('managed', "$managedName.mgd.php");
    $phpData = new PhpData($managedFileName);
    $phpData->useExtensionUtil($info->getExtensionUtilClass());
    $phpData->useTs($localizable);
    $phpData->set($export);
    $ext->builders["$managedName.mgd.php"] = $phpData;
  }

  private function exportAfform($afformName, $info, $ext, $ctx) {
    $basedir = new Path($ctx['basedir']);
    $ext->builders['dirs']->addPath($basedir->string('ang'));

    $fields = \civicrm_api4('Afform', 'getFields', [
      'checkPermissions' => FALSE,
      'where' => [['type', '=', 'Field']],
    ])->indexBy('name');
    // Will throw exception if not found
    $afform = \civicrm_api4('Afform', 'get', [
      'checkPermissions' => FALSE,
      'where' => [['name', '=', $afformName]],
      'select' => ['*', 'search_displays'],
      'layoutFormat' => 'html',
    ])->single();

    // An Afform consists of 2 files - a layout file and a meta file
    $layoutFileName = $basedir->string('ang', "$afformName.aff.html");
    $metaFileName = $basedir->string('ang', "$afformName.aff.php");

    // Export layout file
    $ext->builders["$afformName.aff.html"] = new Content($afform['layout'], $layoutFileName, TRUE);

    // Export meta file
    $meta = $afform;
    unset($meta['name'], $meta['layout'], $meta['search_displays'], $meta['navigation']);
    // Simplify meta file by removing values that match the defaults
    foreach ($meta as $field => $value) {
      if ($field !== 'type' && $value == $fields[$field]['default_value']) {
        unset($meta[$field]);
      }
    }
    $phpData = new PhpData($metaFileName);
    $phpData->useExtensionUtil($info->getExtensionUtilClass());
    $phpData->useTs($this->localizable);
    $phpData->set($meta);
    $ext->builders["$afformName.aff.php"] = $phpData;

    // Export navigation menu item pointing to afform, if present
    if (!empty($afform['server_route'])) {
      $navigation = \civicrm_api4('Navigation', 'get', [
        'checkPermissions' => FALSE,
        'select' => ['id'],
        'where' => [['url', '=', $afform['server_route']], ['is_active', '=', TRUE]],
        // Just the first one; multiple domains are handled by `CRM_Core_ManagedEntities`
        'orderBy' => ['domain_id' => 'ASC'],
      ])->first();
      if ($navigation) {
        $this->exportMgd('Navigation', $navigation['id'], $info, $ext, $ctx);
      }
    }

    // Export embedded search display(s)
    if (!empty($afform['search_displays'])) {
      $searchNames = array_map(function ($item) {
        return explode('.', $item)[0];
      }, $afform['search_displays']);
      $searchIds = \civicrm_api4('SavedSearch', 'get', [
        'checkPermissions' => FALSE,
        'where' => [['name', 'IN', $searchNames]],
      ], ['id']);
      foreach ($searchIds as $id) {
        $this->exportMgd('SavedSearch', $id, $info, $ext, $ctx);
      }
    }
  }

}
