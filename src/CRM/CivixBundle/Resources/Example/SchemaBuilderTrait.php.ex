<?php

use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\Module;
use CRM\CivixBundle\Services;
use CRM\CivixBundle\Utils\Path;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait CRM_CivixBundle_Resources_Example_SchemaBuilderTrait {

  /**
   * @param array $ctx
   *  - basedir: string
   *  - namespace: string
   *  - fullName: string
   * @throws \Exception
   */
  protected function doBuild(&$ctx) {
    $basedir = rtrim($ctx['basedir'], '/' . DIRECTORY_SEPARATOR);
    $xmlSchemaGlob = "xml/schema/{$ctx['namespace']}/*.xml";
    list ($database, $tables) = $this->parseSchema($basedir, $xmlSchemaGlob, $ctx['fullName'] . ':');

    $config = new \stdClass();
    $config->phpCodePath = $basedir . '/';
    $config->sqlCodePath = $basedir . '/sql/';
    $config->tables = $tables;

    $_namespace = ' ' . preg_replace(':/:', '_', $ctx['namespace']);

    foreach ($config->tables as $table) {
      $dao = new \CRM_Core_CodeGen_DAO($config, (string) $table['name'], "{$_namespace}_ExtensionUtil::ts");
      // Don't display gencode's output
      ob_start();
      $dao->run();
      ob_end_clean();
      $daoFileName = $basedir . '/' . ("{$table['base']}{$table['fileName']}");
      // $output->writeln("<info>Write $daoFileName</info>");
    }

    $schema = new \CRM_Core_CodeGen_Schema($config);
    \CRM_Core_CodeGen_Util_File::createDir($config->sqlCodePath);

    /**
     * @param string $generator
     *   The desired $schema->$generator() function which will produce the file.
     * @param string $fileName
     *   The desired basename of the SQL file.
     */
    $createSql = function($generator, $fileName) use ($schema, $config) {
      $filePath = $config->sqlCodePath . $fileName;
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
        // Don't display gencode's output
        ob_start();
        $schema->$generator($fileName);
        ob_end_clean();
      }
    };
    $createSql('generateCreateSql', 'auto_install.sql');
    $createSql('generateDropSql', 'auto_uninstall.sql');
  }

  protected function parseSchema($basedir, $xmlSchemaGlob, $prefix) {
    $orderTables = function (&$tables) {
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
    };

    $resolveForeignKeys = function (&$tables) {
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
    };

    $xmlSchemas = glob("$basedir/$xmlSchemaGlob");

    if (!count($xmlSchemas)) {
      throw new Exception("Could not find files matching '$xmlSchemaGlob'. You may want to run `civix generate:entity` before running this command.");
    }

    $specification = new \CRM_Core_CodeGen_Specification();
    $specification->buildVersion = \CRM_Utils_System::majorVersion();

    foreach ($xmlSchemas as $xmlSchema) {
      $dom = new \DomDocument();
      $xmlString = file_get_contents($xmlSchema);
      $dom->loadXML($xmlString);
      $xml = simplexml_import_dom($dom);
      if (!$xml) {
        throw new \RuntimeException('There is an error in the XML for ' . $xmlSchema);
      }
      $specification->getTable($xml, $database, $tables);
      $name = (string) $xml->name;
      $tables[$name]['name'] = $name;
      $xmlSchemaRelPath = substr($xmlSchema, strlen("$basedir/"));
      $tables[$name]['sourceFile'] = $prefix . $xmlSchemaRelPath;
    }
    $orderTables($tables);
    $resolveForeignKeys($tables);

    return [$database, $tables];
  }

}