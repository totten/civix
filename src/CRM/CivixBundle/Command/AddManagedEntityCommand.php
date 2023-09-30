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

  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:managed')
      ->setDescription('Exports a record in packaged format for distribution in this extension')
      ->addArgument('<EntityName>', InputArgument::REQUIRED, 'API entity name (Ex: "SavedSearch")')
      ->addArgument('<EntityId>', InputArgument::REQUIRED, 'Id of entity to be exported (or name if exporting an Afform)')
      ->setHelp('Uses APIv4 Export to save existing records as .mgd.php files.
Specify the name of the entity and the id.
The file will be saved to the managed directory.

This command also works to export Afforms to the ang directory.');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->assertCurrentFormat();

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);
    $info = $this->getModuleInfo($ctx);

    $entityName = $input->getArgument('<EntityName>');
    $entityId = $input->getArgument('<EntityId>');

    $ext = new Collection();
    $ext->builders['dirs'] = new Dirs();

    // Boot CiviCRM to use api4
    Services::boot(['output' => $output]);

    // Fields that most probably should be wrapped in E::ts()
    $localizable = ['title', 'label', 'description', 'text'];

    // Export Afform
    if ($entityName === 'Afform') {
      $ext->builders['dirs']->addPath($basedir->string('ang'));
      $afformName = $entityId;

      $fields = \civicrm_api4('Afform', 'getFields', [
        'checkPermissions' => FALSE,
        'where' => [['type', '=', 'Field']],
      ])->indexBy('name');
      $afform = \civicrm_api4('Afform', 'get', [
        'where' => [['name', '=', $afformName]],
        'checkPermissions' => FALSE,
        'select' => ['*', 'search_displays'],
        'layoutFormat' => 'html',
      ])->first();
      if (!$afform) {
        $output->writeln("Error: Afform $afformName not found.");
        return 1;
      }

      // An Afform consists of 2 files - a layout file and a meta file
      $layoutFileName = $basedir->string('ang', $entityId . '.aff.html');
      $metaFileName = $basedir->string('ang', $entityId . '.aff.php');

      // Export layout file
      $ext->builders['afformLayout' . $afformName] = new Content($afform['layout'], $layoutFileName, TRUE);

      // If Afform contains embedded search displays, queue those to be exported as .mgd.php
      if (!empty($afform['search_displays'])) {
        $searchNames = array_map(function ($item) {
          return explode('.', $item)[0];
        }, $afform['search_displays']);
        $entityId = (array) \civicrm_api4('SavedSearch', 'get', [
          'where' => [['name', 'IN', $searchNames]],
          'checkPermissions' => FALSE,
        ], ['id']);
        if ($entityId) {
          $entityName = 'SavedSearch';
        }
      }

      // Export meta file
      unset($afform['search_displays'], $afform['name'], $afform['layout']);
      // Simplify meta file by removing values that match the defaults
      foreach ($afform as $field => $value) {
        if ($value === $fields[$field]['default_value']) {
          unset($afform[$field]);
        }
      }
      $phpData = new PhpData($metaFileName);
      $phpData->useExtensionUtil($info->getExtensionUtilClass());
      $phpData->useTs($localizable);
      $phpData->set($afform);
      $ext->builders['afformMeta' . $afformName] = $phpData;
    }

    // Export mgd.php
    if ($entityName !== 'Afform') {
      $ext->builders['mixins'] = new Mixins($info, $basedir->string('mixin'), ['mgd-php@1.0']);
      $ext->builders['dirs']->addPath($basedir->string('managed'));
      foreach ((array) $entityId as $id) {
        $export = (array) \civicrm_api4($entityName, 'export', [
          'id' => $id,
          'checkPermissions' => FALSE,
        ]);
        if (!$export) {
          $output->writeln("Error: $entityName $id not found.");
          return 1;
        }
        $managedName = $export[0]['name'];
        $entityCount = count($export);

        // Lookup entity-specific fields that should be wrapped in E::ts()
        foreach ($export as $item) {
          $fields = (array) \civicrm_api4($item['entity'], 'getFields', [
            'where' => [['localizable', '=', TRUE]],
            'checkPermissions' => FALSE,
          ], ['name']);
          $localizable = array_merge($localizable, $fields);
        }

        $output->writeln("Exporting $managedName with $entityCount " . ($entityCount === 1 ? 'record.' : 'records.'));
        $managedFileName = $basedir->string('managed', $managedName . '.mgd.php');
        $phpData = new PhpData($managedFileName);
        $phpData->useExtensionUtil($info->getExtensionUtilClass());
        $phpData->useTs($localizable);
        $phpData->set($export);
        $ext->builders['mgd.php' . $id] = $phpData;
      }
    }

    $ext->builders['info'] = $info;

    $ext->loadInit($ctx);
    $ext->save($ctx, $output);
    return 0;
  }

}
