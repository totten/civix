<?php
namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Mixins;
use Civix;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Collection;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\PhpData;
use CRM\CivixBundle\Builder\Template;
use CRM\CivixBundle\Utils\Path;
use CRM\CivixBundle\Utils\Naming;

use Exception;

class AddEntityCommand extends AbstractCommand {
  const API_VERSION = 4;

  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:entity')
      ->setDescription('Add a new Entity+API+Sql Table to a CiviCRM Module-Extension')
      ->addArgument('<EntityName>', InputArgument::REQUIRED, 'The brief, unique name of the entity")')
      ->addOption('table-name', NULL, InputOption::VALUE_OPTIONAL, 'The SQL table name. (see usage)')
      ->setHelp('Add a new SQL-based Entity to a CiviCRM Module-Extension.
It includes an .entityType.php file, an Api4 file, and a DAO file.

In most cases generate:entity is able to derive a suitable snake_case table name
from The CamelCase <EntityName>. However, in some instances (notably when the
entity contains a number or a capitalised acronym) the table name may differ
from your expectations. In these cases, you may wish to set the table name
explicitly.');

  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    // load Civi to get access to civicrm_api_get_function_name
    Civix::boot(['output' => $output]);
    $civicrm_api3 = Civix::api3();
    if (!$civicrm_api3 || !$civicrm_api3->local) {
      $output->writeln("<error>Require access to local CiviCRM source tree. Configure civicrm_api3_conf_path.</error>");
      return 1;
    }

    $this->assertCurrentFormat();

    $apiVersions = [self::API_VERSION];

    Civix::generator()->addUpgrader('if-forced');

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);

    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);

    if (!preg_match('/^[A-Za-z0-9]+$/', $input->getArgument('<EntityName>'))) {
      throw new Exception("Entity name must be alphanumeric camel-case");
    }

    $ctx['entityNameCamel'] = ucfirst($input->getArgument('<EntityName>'));
    $ctx['tableName'] = $input->getOption('table-name') ? $input->getOption('table-name') : Naming::createTableName($input->getArgument('<EntityName>'));

    $mixins = new Mixins($info, $basedir->string('mixin'), ['entity-types-php@2.0']);
    $mixins->save($ctx, $output);
    $info->save($ctx, $output);

    $ctx['api4File'] = $basedir->string('Civi', 'Api4', $ctx['entityNameCamel'] . '.php');
    $ctx['daoClassName'] = strtr($ctx['namespace'], '/', '_') . '_DAO_' . $input->getArgument('<EntityName>');
    $ctx['daoClassFile'] = $basedir->string(strtr($ctx['daoClassName'], '_', '/') . '.php');
    $ctx['baoClassName'] = strtr($ctx['namespace'], '/', '_') . '_BAO_' . $input->getArgument('<EntityName>');
    $ctx['baoClassFile'] = $basedir->string(strtr($ctx['baoClassName'], '_', '/') . '.php');
    $ctx['entityTypeFile'] = $basedir->string('schema', $input->getArgument('<EntityName>') . '.entityType.php');
    $ctx['extensionName'] = $info->getExtensionName();

    $ext = new Collection();
    $ext->builders['dirs'] = new Dirs([
      dirname($ctx['baoClassFile']),
    ]);
    $ext->builders['dirs']->save($ctx, $output);

    $hasPhpUnit = FALSE;

    if (in_array('4', $apiVersions)) {
      $ext->builders['dirs']->addPath(dirname($ctx['api4File']));
      $ext->builders['api4.php'] = new Template('entity-api4.php.php', $ctx['api4File'], FALSE, Civix::templating());
    }
    $ext->builders['bao.php'] = new Template('entity-bao.php.php', $ctx['baoClassFile'], FALSE, Civix::templating());

    if (!file_exists($ctx['entityTypeFile'])) {
      $entityDefn = $this->createDefaultSchema($ctx['entityNameCamel'], $ctx['tableName'], $ctx['daoClassName']);
      $ext->builders['entityType.php'] = new PhpData($ctx['entityTypeFile']);
      $ext->builders['entityType.php']->useExtensionUtil($info->getExtensionUtilClass());
      $ext->builders['entityType.php']->useTs(['title', 'title_plural', 'label', 'description']);
      $ext->builders['entityType.php']->setCallbacks(['getPaths', 'getFields', 'getIndices', 'getInfo']);
      $ext->builders['entityType.php']->set($entityDefn);
    }

    $ext->init($ctx);
    $ext->save($ctx, $output);

    Civix::generator()->addDaoClass($ctx['daoClassName'], $ctx['tableName'], 'if-forced');

    if ($hasPhpUnit) {
      Civix::generator()->addPhpunit();
    }

    Civix::generator()->updateModuleCivixPhp();

    if ($apiVersions == [4]) {
      $output->writeln('<comment>Generated API skeletons for APIv4.</comment>');
    }

    $output->writeln('<comment>Note: no changes have been made to the database. You can update the database by uninstalling and re-enabling the extension.</comment>');

    return 0;
  }

  /**
   * @param string $entityNameCamel
   *   Ex: 'Mailing'
   * @param string $tableName
   *   Ex: 'civicrm_mailing'
   * @param string $daoClassName
   *   Ex: 'CRM_Foo_DAO_Mailing'
   * @return array
   */
  protected function createDefaultSchema(string $entityNameCamel, string $tableName, string $daoClassName): array {
    return [
      'name' => $entityNameCamel,
      'table' => $tableName,
      'class' => $daoClassName,
      'getInfo' => [
        'title' => $entityNameCamel,
        'title_plural' => \CRM_Utils_String::pluralize($entityNameCamel),
        'description' => 'FIXME',
        'log' => TRUE,
      ],
      'getFields' => [
        'id' => [
          'title' => 'ID',
          'sql_type' => 'int unsigned',
          'input_type' => 'Number',
          'required' => TRUE,
          'description' => sprintf('Unique %s ID', $entityNameCamel),
          'primary_key' => TRUE,
          'auto_increment' => TRUE,
        ],
        'contact_id' => [
          'title' => 'Contact ID',
          'sql_type' => 'int unsigned',
          'input_type' => 'EntityRef',
          'description' => 'FK to Contact',
          'entity_reference' => [
            'entity' => 'Contact',
            'key' => 'id',
            'on_delete' => 'CASCADE',
          ],
        ],
      ],
      'getIndices' => [],
      'getPaths' => [],
    ];
  }

}
