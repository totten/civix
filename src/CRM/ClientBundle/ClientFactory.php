<?php

namespace CRM\ClientBundle;

/**
 * An adaptor which allows us to use CiviCRM's 'class.api.php' as
 * a Symfony service.
 */
class ClientFactory {

  /**
   * Instantiate a configured API connection
   *
   * @return \civicrm_api3
   */
  public function get() {
    $origDir = $this->getPwd();

    list ($cmsRoot, $civicrmConfigPhp) = $this->findCivicrmConfigPhp($origDir);
    if (!is_dir($cmsRoot)) {
      throw new \Exception('Failed to locate CMS. Please call civix from somewhere under the CMS root.');
    }
    if (!file_exists($civicrmConfigPhp)) {
      throw new \Exception('Failed to locate civicrm.config.php. Please call civix from somewhere under the CMS root.');
    }
    $this->bootstrap($cmsRoot, $civicrmConfigPhp);

    require_once __DIR__ . '/class.api.php';
    $config = array();
    $result = new \civicrm_api3($config);

    chdir($origDir);
    return $result;
  }

  /**
   * @param string $startDir the directory in which to start the start
   * @return null|array (0 => $cmsRoot, 1 => $civicrmConfigPhpPath)
   */
  private function findCivicrmConfigPhp($startDir) {
    $parts = explode('/', str_replace('\\', '/', $startDir));
    while (!empty($parts)) {
      $basePath = implode('/', $parts);
      $relPaths = array(
        'wp-content/plugins/civicrm/civicrm/civicrm.config.php',
        'administrator/components/com_civicrm/civicrm/civicrm.config.php',
        'sites/default/modules/civicrm/civicrm.config.php', // check 'default' first
        'sites/default/modules/*/civicrm/civicrm.config.php', // check 'default' first
        'sites/*/modules/civicrm/civicrm.config.php',
        'sites/*/modules/*/civicrm/civicrm.config.php',
      );
      foreach ($relPaths as $relPath) {
        $matches = glob("$basePath/$relPath");
        if (!empty($matches)) {
          return array($basePath, $matches[0]);
        }
      }
      array_pop($parts);
    }
    return NULL;
  }

  private function bootstrap($cmsRoot, $civicrm_config_path) {
    define('CIVICRM_CMSDIR', $cmsRoot);
    require_once $civicrm_config_path;

    // so the configuration works with php-cli
    $_SERVER['PHP_SELF'] = "/index.php";
    $_SERVER['HTTP_HOST'] = 'localhost'; // $this->_site;
    $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
    $_SERVER['SERVER_SOFTWARE'] = NULL;
    $_SERVER['REQUEST_METHOD'] = 'GET';

    // SCRIPT_FILENAME needed by CRM_Utils_System::cmsRootPath
    $_SERVER['SCRIPT_FILENAME'] = __FILE__;

    // CRM-8917 - check if script name starts with /, if not - prepend it.
    if (ord($_SERVER['SCRIPT_NAME']) != 47) {
      $_SERVER['SCRIPT_NAME'] = '/' . $_SERVER['SCRIPT_NAME'];
    }

    $config = \CRM_Core_Config::singleton();

    // HTTP_HOST will be 'localhost' unless overwritten with the -s argument.
    // Now we have a Config object, we can set it from the Base URL.
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
      $_SERVER['HTTP_HOST'] = preg_replace(
        '!^https?://([^/]+)/$!i',
        '$1',
        $config->userFrameworkBaseURL);
    }

    global $civicrm_root;
    if (!\CRM_Utils_System::loadBootstrap(array(), FALSE, FALSE, $civicrm_root)) {
      throw new \Exception("Failed to bootstrap CMS");
      // return FALSE;
    }

    return TRUE;
  }

  private function getPwd() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      return getcwd();
    }
    else {
      exec('pwd', $output);
      return trim(implode("\n", $output));
    }
  }

}
