<?php

/**
 * Trait CRM_CivixBundle_Resources_Example_SchemaBuilderTrait
 *
 * This provides the 'createSchemaBuilder' helper, which reads in
 * 'xml/schema/' files and produces DAOs and/or SQL.
 *
 * It would be more natural to model this a standalone class. However,
 * modeling this is a trait with a self-sufficient inner-class gives an
 * advantage for packaging purposes:
 *
 * 1. We can use it in civix runtime ('AddEntityBoilerplateCommand')
 * 2. We can also use it inline for generated code ('CRM_*_Upgrader_Base').
 * 3. We can store/edit it as plain-old PHP (rather than a meta-PHP template).
 */
trait CRM_CivixBundle_Resources_Example_SchemaBuilderTrait {

  /**
   * @param array $ctx
   *  - basedir: string
   *  - namespace: string
   *  - fullName: string
   * @return object
   */
  protected function createSchemaBuilder($ctx) {
    return new class($ctx) {

      protected $basedir;

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
      protected $files = [];

      /**
       * SchemaBuilder constructor.
       */
      public function __construct($ctx) {
        foreach (['namespace', 'basedir', 'fullName'] as $field) {
          if (empty($ctx[$field])) {
            throw new \RuntimeException("Missing required argument: $field");
          }
        }
        $_namespace = ' ' . preg_replace(':/:', '_', $ctx['namespace']);
        $this->tsFunction = "{$_namespace}_ExtensionUtil::ts";
        $this->basedir = rtrim($ctx['basedir'], '/' . DIRECTORY_SEPARATOR);
        $this->sourceFilePrefix = empty($ctx['fullName']) ? '' : ($ctx['fullName'] . ':');
        $this->specification = new \CRM_Core_CodeGen_Specification();
        $this->specification->buildVersion = \CRM_Utils_System::majorVersion();
      }

      /**
       * Parse the schema XML file(s).
       *
       * @param string $xmlSchemaGlob
       *   XML file(s), expressed relative to the base dir.
       *   Globs accepted.
       *
       * @return $this
       */
      public function addXml($xmlSchemaGlob) {
        $absXmlSchemaGlob = $this->basedir . '/' . $xmlSchemaGlob;
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
          $sourcePath = substr($xmlSchema, strlen("{$this->basedir}/"));
          $this->tables[$name]['sourceFile'] = $this->sourceFilePrefix . $sourcePath;
        }

        $this->orderTables($this->tables);
        $this->resolveForeignKeys($this->tables);

        return $this;
      }

      /**
       * Create DAO files.
       *
       * @return $this
       */
      public function generateDaoFiles() {
        $config = new \stdClass();
        $config->phpCodePath = "{$this->basedir}/";
        //    $config->sqlCodePath = $basedir->string('sql/');
        $config->tables = $this->tables;

        foreach ($this->tables as $table) {
          $dao = new \CRM_Core_CodeGen_DAO($config, (string) $table['name'], $this->tsFunction);
          // Don't display gencode's output
          ob_start();
          $dao->run();
          ob_end_clean();
          $file = $this->basedir . "/{$table['base']}{$table['fileName']}";
          $this->files[] = ['type' => 'dao', 'file' => $file];
        }

        return $this;
      }

      /**
       * @param string $type
       *   The type of SQL file to generate, 'CREATE' or 'DROP'.
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
          return array_shift($schema->$generator());
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
        $data = $this->generateSql($type);

        if (!is_dir(dirname($filePath))) {
          mkdir(dirname($filePath), 0777, TRUE);
        }
        if (!file_put_contents($filePath, $data)) {
          throw new \Exception("Failed to write data to {$filePath}");
        }

        $this->files[] = ['type' => $type, 'file' => $filePath];

        return $this;
      }

      /**
       * @return array
       *   Each item has:
       *     type: string, e.g. 'dao' or 'createSql'
       *     file: string, file-path
       */
      public function getFiles(): array {
        return $this->files;
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

    };
  }

}
