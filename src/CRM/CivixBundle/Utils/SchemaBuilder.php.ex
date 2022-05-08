<?php

namespace CRM\CivixBundle\Utils;

class SchemaBuilder {

  /**
   * @var string
   */
  protected $tsFunction = 'ts';

  protected $sourceFilePrefix;

  /**
   * @var \CRM_Core_CodeGen_Specification
   */
  protected $specification;

  protected $database = [];

  /**
   * @var array
   */
  protected $tables = [];

  /**
   * @var array
   */
  protected $outputs = [];

  /**
   * @param string $prefix
   *
   * @return $this
   */
  public function create($prefix = '') {
    return new static($prefix);
  }

  /**
   * SchemaBuilder constructor.
   */
  public function __construct($prefix = '') {
    $this->sourceFilePrefix = $prefix;
    $this->specification = new \CRM_Core_CodeGen_Specification();
    $this->specification->buildVersion = \CRM_Utils_System::majorVersion();
  }

  public function addXml($basedir, $xmlSchemaGlob) {
    $absXmlSchemaGlob = $basedir->string($xmlSchemaGlob);
    $xmlSchemas = glob($absXmlSchemaGlob);

    if (!count($xmlSchemas)) {
      throw new \Exception("Could not find files matching '$xmlSchemaGlob'. You may want to run `civix generate:entity` before running this command.");
    }

    foreach ($xmlSchemas as $xmlSchema) {
      $dom = new \DomDocument();
      $xmlString = file_get_contents($xmlSchema);
      $dom->loadXML($xmlString);
      $xml = simplexml_import_dom($dom);
      if (!$xml) {
        throw new \Exception('There is an error in the XML for ' . $xmlSchema);
      }
      $this->specification->getTable($xml, $this->database, $this->tables);
      $name = (string) $xml->name;
      $this->tables[$name]['name'] = $name;
      $sourcePath = substr($xmlSchema, strlen($basedir->string()));
      $this->tables[$name]['sourceFile'] = $this->sourceFilePrefix . $sourcePath;
    }

    $this->orderTables($this->tables);
    $this->resolveForeignKeys($this->tables);

    return $this;
  }

  public function generateDaoFiles($basedir) {
    $config = new \stdClass();
    $config->phpCodePath = $basedir->string('');
    //    $config->sqlCodePath = $basedir->string('sql/');
    $config->tables = $this->tables;

    foreach ($this->tables as $table) {
      $dao = new \CRM_Core_CodeGen_DAO($config, (string) $table['name'], $this->tsFunction);
      // Don't display gencode's output
      ob_start();
      $dao->run();
      ob_end_clean();
      $file = $basedir->string("{$table['base']}{$table['fileName']}");
      $this->outputs[] = ['type' => 'dao', 'file' => $file];
    }

    return $this;
  }

  /**
   * @param string $type
   *   The type of SQL file to generate, 'CREATE' or 'DROP'.
   * @param string $filePath
   *
   * @return string
   * @throws \Exception
   */
  public function generateSql($type) {
    $generators = ['CREATE' => 'generateCreateSql', 'DROP' => 'generateDropSql'];
    $generator = $generators[strtoupper($type)] ?? NULL;
    if (!$generator) {
      throw new \Exception("Invalid SQL content type: $type");
    }

    $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . bin2hex(random_bytes(12));
    $config = new \stdClass();
    $config->sqlCodePath = dirname($tmpFile);
    $config->tables = $this->tables;

    $schema = new \CRM_Core_CodeGen_Schema($config);

    // We're poking into an internal class+function (`$schema->$generator()`) that changed in v5.23.
    // Beginning in 5.23: $schema->$function() returns an array with file content.
    // Before 5.23: $schema->$function($fileName) creates $fileName and returns void.
    if (version_compare(\CRM_Utils_System::version(), '5.23.alpha1', '>=')) {
      return $schema->$generator();
    }
    else {
      ob_start();
      $schema->$generator(basename($tmpFile));
      ob_end_clean();

      $sql = file_get_contents($tmpFile);
      @unlink($tmpFile);
      return $sql;
    }
  }

  /**
   * @param string $type
   *   The type of SQL file to generate, 'CREATE' or 'DROP'.
   * @param string $filePath
   *
   * @return $this
   * @throws \Exception
   */
  public function generateSqlFile($type, $filePath) {
    $generators = ['CREATE' => 'generateCreateSql', 'DROP' => 'generateDropSql'];
    $generator = $generators[strtoupper($type)] ?? NULL;
    if (!$generator) {
      throw new \Exception("Invalid SQL content type: $type");
    }

    $config = new \stdClass();
    $config->sqlCodePath = dirname($filePath);
    $config->tables = $this->tables;

    $schema = new \CRM_Core_CodeGen_Schema($config);
    \CRM_Core_CodeGen_Util_File::createDir(dirname($filePath));

    // We're poking into an internal class+function (`$schema->$generator()`) that changed in v5.23.
    // Beginning in 5.23: $schema->$function() returns an array with file content.
    // Before 5.23: $schema->$function($fileName) creates $fileName and returns void.
    if (version_compare(\CRM_Utils_System::version(), '5.23.alpha1', '>=')) {
      $data = $schema->$generator();
      if (!file_put_contents($filePath, reset($data))) {
        throw new \Exception("Failed to write data to {$filePath}");
      }
    }
    else {
      ob_start();
      $schema->$generator(basename($filePath));
      ob_end_clean();
    }

    $this->outputs[] = ['type' => $generator, 'file' => $filePath];

    return $this;
  }

  /**
   * @return array
   *   Each item has:
   *     type: string, e.g. 'dao' or 'createSql'
   *     file: string, file-path
   */
  public function getOutputs(): array {
    return $this->outputs;
  }

  public function setTs(string $func) {
    $this->tsFunction = $func;
    return $this;
  }

  private function orderTables(&$tables) {
    $ordered = [];

    while (count($tables)) {
      foreach ($tables as $k => $table) {
        if (!isset($table['foreignKey'])) {
          $ordered[$k] = $table;
          unset($tables[$k]);
        }
        foreach ($table['foreignKey'] as $fKey) {
          if (in_array($fKey['table'], array_keys($tables))) {
            continue;
          }
          $ordered[$k] = $table;
          unset($tables[$k]);
        }
      }
    }
    $tables = $ordered;

    return $this;
  }

  private function resolveForeignKeys(&$tables) {
    foreach ($tables as &$table) {
      if (isset($table['foreignKey'])) {
        foreach ($table['foreignKey'] as &$key) {
          if (isset($tables[$key['table']])) {
            $key['className'] = $tables[$key['table']]['className'];
            $key['fileName'] = $tables[$key['table']]['fileName'];
            $table['fields'][$key['name']]['FKClassName'] = $key['className'];
          }
          else {
            $key['className'] = \CRM_Core_DAO_AllCoreTables::getClassForTable($key['table']);
            $key['fileName'] = $key['className'] . '.php';
            $table['fields'][$key['name']]['FKClassName'] = $key['className'];
          }
        }
      }
    }
    return $this;
  }

}