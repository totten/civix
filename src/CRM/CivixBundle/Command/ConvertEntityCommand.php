<?php

namespace CRM\CivixBundle\Command;

use CRM\CivixBundle\Builder\Mixins;
use Civix;
use CRM\CivixBundle\Builder\PhpData;
use CRM\CivixBundle\Utils\Files;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Utils\Path;

class ConvertEntityCommand extends AbstractCommand {

  protected function configure() {
    $this
      ->setName('convert-entity')
      ->setDescription('Convert legacy xml entity declarations to newer php format')
      ->setHelp(
        "This command will convert entities from legacy xml/schema to current .entityType.php format\n"
      );
    parent::configure();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    Civix::boot(['output' => $output]);
    $this->assertCurrentFormat();

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \Civix::extDir();
    $basedir = new Path($ctx['basedir']);
    $info = $this->getModuleInfo($ctx);

    // Switch mixin from v1 to v2
    //  $mixins = new Mixins($info, $basedir->string('mixin'));
    //  $mixins->removeMixin('entity-types-php@1');
    //  $mixins->addMixin('entity-types-php@2');
    //  $mixins->save($ctx, $output);

    $xmlFiles = glob($basedir->string('xml/schema/CRM/*/*.xml'));

    $thisTables = self::getTablesForThisExtension($xmlFiles);

    foreach ($xmlFiles as $fileName) {
      $entity = self::convertXmlToEntity($fileName, $thisTables);
      if (!$entity) {
        continue;
      }
      $entityFile = $basedir->string('schema', $entity['name'] . '.entityType.php');
      if (file_exists($entityFile)) {
        // throw new \Exception("File schema/{$entity['name']}.php already exists. Aborting.");
        unlink($entityFile);
      }
      $phpData = new PhpData($entityFile);
      $phpData->useExtensionUtil($info->getExtensionUtilClass());
      $phpData->useTs(['title', 'title_plural', 'label', 'description']);
      $phpData->setLiterals(['serialize', 'data_type']);
      $phpData->setCallbacks(['getInfo', 'getPaths', 'getFields', 'getIndices']);
      $phpData->set($entity);
      $phpData->save($ctx, $output);
    }

    // Cleanup old files
    //  array_map('unlink', $xmlFiles);
    //  array_map('unlink', glob($basedir->string('xml/schema/CRM/*/*.entityType.php')));
    //  unlink($basedir->string('sql/auto_install.sql'));
    //  unlink($basedir->string('sql/auto_uninstall.sql'));

    return 0;
  }

  public static function getTablesForThisExtension($xmlFiles): array {
    $tables = [];
    foreach ($xmlFiles as $fileName) {
      [$xml, $error] = \CRM_Utils_XML::parseFile($fileName);
      if ($error) {
        throw new \Exception($error);
      }
      $tableName = self::toString('name', $xml);
      $entityName = self::toString('entity', $xml) ?? self::toString('class', $xml);
      $tables[$tableName] = $entityName;
    }
    return $tables;
  }

  public static function convertXmlToEntity(string $fileName, $thisTables):? array {
    [$xml, $error] = \CRM_Utils_XML::parseFile($fileName);
    if ($error || !empty($xml->drop)) {
      return NULL;
    }
    $name = self::toString('entity', $xml) ?: self::toString('class', $xml);
    $title = self::toString('title', $xml) ?: \CRM_Utils_Schema::composeTitle($name);
    $entity = [
      'name' => $name,
      'table' => self::toString('name', $xml),
      'class' => str_replace('/', '_', $xml->base) . '_DAO_' . $xml->class,
    ];
    $info = [
      'title' => $title,
      'title_plural' => self::toString('titlePlural', $xml) ?: \CRM_Utils_String::pluralize($title),
      'description' => self::toString('description', $xml) ?? self::toString('comment', $xml) ?? 'FIXME',
      'log' => self::toBool('log', $xml),
      'add' => self::toString('add', $xml) ?? '1.0',
    ];
    $icon = self::toString('icon', $xml);
    if ($icon) {
      $info['icon'] = $icon;
    }
    $labelField = self::toString('labelField', $xml);
    if ($labelField) {
      $info['label_field'] = $labelField;
    }
    $entity['getInfo'] = $info;
    if (isset($xml->paths)) {
      $entity['getPaths'] = (array) $xml->paths;
    }
    if (isset($xml->index)) {
      $entity['getIndices'] = self::getIndicesFromXml($xml);
    }
    $entity['getFields'] = self::getFieldsFromXml($xml, $thisTables);
    return $entity;
  }

  private static function getIndicesFromXml($xml): array {
    $indices = [];
    foreach ($xml->index as $index) {
      if (isset($index->drop)) {
        continue;
      }
      $name = self::toString('name', $index);
      $indices[$name] = [
        'fields' => [],
      ];
      foreach ($index->fieldName as $field) {
        $fieldName = (string) $field;
        $length = isset($field['length']) ? (int) $field['length'] : TRUE;
        $indices[$name]['fields'][$fieldName] = $length;
      }
      if (self::toBool('unique', $index)) {
        $indices[$name]['unique'] = TRUE;
      }
      $add = self::toString('add', $index);
      if ($add) {
        $indices[$name]['add'] = $add;
      }
    }
    return $indices;
  }

  private static function getFieldsFromXml($xml, $thisTables): array {
    $fields = [];
    foreach ($xml->field as $fieldXml) {
      if (isset($fieldXml->drop)) {
        continue;
      }
      $name = self::toString('name', $fieldXml);
      $typeAttributes = \CRM_Utils_Schema::getTypeAttributes($fieldXml);
      if ($typeAttributes['crmType'] == 'CRM_Utils_Type::T_BOOLEAN') {
        $typeAttributes['sqlType'] = 'boolean';
      }
      $fields[$name] = [
        'title' => self::toString('title', $fieldXml) ?: \CRM_Utils_Schema::composeTitle($name),
        'sql_type' => $typeAttributes['sqlType'],
        'input_type' => ((string) $fieldXml->html->type) ?: NULL,
      ];
      if (!empty($fieldXml->crmType)) {
        $fields[$name]['data_type'] = $typeAttributes['crmType'];
      }
      $boolValues = [
        'required',
        'deprecated',
        'readonly',
        'localizable',
      ];
      foreach ($boolValues as $boolValue) {
        if (self::toBool($boolValue, $fieldXml)) {
          $fields[$name][$boolValue] = TRUE;
        }
      }
      $stringValues = [
        'comment' => 'description',
        'add' => 'add',
        'uniqueName' => 'unique_name',
        'uniqueTitle' => 'unique_title',
        'contactType' => 'contact_type',
        'component' => 'component',
        'localize_context' => 'localize_context',
        'collate' => 'collate',
      ];
      foreach ($stringValues as $xmlKey => $phpKey) {
        $value = self::toString($xmlKey, $fieldXml);
        if ($value) {
          $fields[$name][$phpKey] = $value;
        }
      }
      if (isset($fieldXml->default)) {
        $default = (string) $fieldXml->default;
        if ($default === 'NULL') {
          $default = NULL;
        }
        else {
          $default = trim($default, '"\'');
          if (str_contains($typeAttributes['sqlType'], 'int')) {
            $default = (int) $default;
          }
          if (isset($default) && $typeAttributes['sqlType'] === 'boolean') {
            $default = (bool) $default;
          }
        }
        $fields[$name]['default'] = $default === 'NULL' ? NULL : $default;
      }
      if (!empty($fieldXml->serialize)) {
        $fields[$name]['serialize'] = 'CRM_Core_DAO::SERIALIZE_' . $fieldXml->serialize;
      }
      if (!empty($fieldXml->permission)) {
        $fields[$name]['permission'] = \CRM_Utils_Schema::getFieldPermission($fieldXml);
      }
      $usage = \CRM_Utils_Schema::getFieldUsage($fieldXml);
      $usage = array_keys(array_filter($usage));
      if ($usage) {
        $fields[$name]['usage'] = $usage;
      }
      $attributes = isset($fieldXml->html) ? self::snakeCaseKeys((array) $fieldXml->html) : [];
      unset($attributes['type']);
      if (!empty($fieldXml->length)) {
        $attributes['maxlength'] = (int) $fieldXml->length;
      }
      if ($attributes) {
        foreach (['rows', 'cols'] as $intKey) {
          if (isset($attributes[$intKey])) {
            $attributes[$intKey] = (int) $attributes[$intKey];
          }
        }
        $fields[$name]['input_attrs'] = $attributes;
      }
      if (!empty($fieldXml->pseudoconstant)) {
        $fields[$name]['pseudoconstant'] = self::snakeCaseKeys((array) $fieldXml->pseudoconstant);
      }
      if (!empty($fields[$name]['pseudoconstant']['suffixes'])) {
        $fields[$name]['pseudoconstant']['suffixes'] = explode(',', $fields[$name]['pseudoconstant']['suffixes']);
      }
    }
    foreach ($xml->foreignKey ?? [] as $fkXml) {
      if (empty($fkXml->drop)) {
        $fkTable = self::toString('table', $fkXml);
        $fieldName = self::toString('name', $fkXml);
        $fields[$fieldName]['entity_reference'] = [
          'entity' => $thisTables[$fkTable] ?? \CRM_Core_DAO_AllCoreTables::getEntityNameForTable($fkTable),
          'key' => (string) ($fkXml->key ?? 'id'),
          'on_delete' => strtoupper(self::toString('onDelete', $fkXml) ?? 'SET NULL'),
        ];
      }
    }
    foreach ($xml->dynamicForeignKey ?? [] as $dfkXml) {
      if (empty($dfkXml->drop)) {
        $fieldName = self::toString('idColumn', $fkXml);
        $fields[$fieldName]['entity_reference'] = [
          'dynamic_entity' => (string) $dfkXml->typeColumn,
          'key' => (string) ($dfkXml->key ?? 'id'),
        ];
      }
    }
    foreach ($xml->primaryKey ?? [] as $primaryKey) {
      $fieldName = self::toString('name', $primaryKey);
      $fields[$fieldName]['primary_key'] = TRUE;
      if (self::toBool('autoincrement', $primaryKey)) {
        $fields[$fieldName]['auto_increment'] = TRUE;
      }
    }
    // Attempt to set input_type if missing
    foreach ($fields as $name => $field) {
      if (isset($field['input_type']) || !empty($field['readonly'])) {
        continue;
      }
      if (!empty($field['entity_reference'])) {
        $fields[$name]['input_type'] = 'EntityRef';
      }
      elseif (!empty($field['pseudoconstant'])) {
        $fields[$name]['input_type'] = 'Select';
      }
      elseif ($field['sql_type'] === 'boolean') {
        $fields[$name]['input_type'] = 'CheckBox';
      }
      elseif (str_contains($field['sql_type'], 'date')) {
        $fields[$name]['input_type'] = 'Select Date';
      }
      elseif (str_contains($field['sql_type'], 'int')) {
        $fields[$name]['input_type'] = 'Number';
      }
      elseif (str_contains($field['sql_type'], 'char')) {
        $fields[$name]['input_type'] = 'Text';
      }
      elseif (str_contains($field['sql_type'], 'text')) {
        $fields[$name]['input_type'] = 'TextArea';
      }
    }
    return $fields;
  }

  private static function snakeCaseKeys(array $arr): array {
    return \CRM_Utils_Array::rekey($arr, ['CRM_Utils_String', 'convertStringToSnakeCase']);
  }

  private static function toString(string $key, \SimpleXMLElement $xml): ?string {
    if (isset($xml->$key)) {
      return (string) $xml->$key;
    }
    return NULL;
  }

  private static function toBool(string $key, \SimpleXMLElement $xml): ?bool {
    if (isset($xml->$key)) {
      $value = strtolower((string) $xml->$key);
      return $value === 'true' || $value === '1';
    }
    return NULL;
  }

}
