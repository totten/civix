<?php

trait CRM_CivixBundle_Resources_Example_UpgraderTasksTrait {

  /**
   * Run a CustomData file.
   *
   * @param string $relativePath
   *   the CustomData XML file path (relative to this extension's dir)
   * @return bool
   */
  public function executeCustomDataFile($relativePath) {
    $xml_file = $this->extensionDir . '/' . $relativePath;
    return $this->executeCustomDataFileByAbsPath($xml_file);
  }

  /**
   * Run a CustomData file
   *
   * @param string $xml_file
   *   the CustomData XML file path (absolute path)
   *
   * @return bool
   */
  protected function executeCustomDataFileByAbsPath($xml_file) {
    $import = new CRM_Utils_Migrate_Import();
    $import->run($xml_file);
    return TRUE;
  }

  /**
   * Run a SQL file.
   *
   * @param string $tplFile
   *   The SQL file path (relative to this extension's dir, or absolute)
   *
   * @return bool
   */
  public function executeSqlFile($tplFile) {
    $tplFile = CRM_Utils_File::isAbsolute($tplFile) ? $tplFile : $this->extensionDir . DIRECTORY_SEPARATOR . $tplFile;
    CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $tplFile);
    return TRUE;
  }

  /**
   * Run the sql commands in the specified file.
   *
   * @param string $tplFile
   *   The SQL file path (relative to this extension's dir, or absolute).
   *   Ex: "sql/mydata.mysql.tpl".
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function executeSqlTemplate($tplFile) {
    // Assign multilingual variable to Smarty.
    $upgrade = new CRM_Upgrade_Form();

    $tplFile = CRM_Utils_File::isAbsolute($tplFile) ? $tplFile : $this->extensionDir . DIRECTORY_SEPARATOR . $tplFile;
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('domainID', CRM_Core_Config::domainID());
    CRM_Utils_File::sourceSQLFile(
      CIVICRM_DSN, $smarty->fetch($tplFile), NULL, TRUE
    );
    return TRUE;
  }

  /**
   * Run one SQL query.
   *
   * This is just a wrapper for CRM_Core_DAO::executeSql, but it
   * provides syntactic sugar for queueing several tasks that
   * run different queries
   *
   * @return bool
   */
  public function executeSql($query, $params = []) {
    // FIXME verify that we raise an exception on error
    CRM_Core_DAO::executeQuery($query, $params);
    return TRUE;
  }

}
