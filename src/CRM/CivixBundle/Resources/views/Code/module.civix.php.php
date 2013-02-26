<?php
echo "<?php\n";
$_namespace = preg_replace(':/:','_',$namespace);
?>

// AUTO-GENERATED FILE -- Civix may overwrite any changes made to this file

/**
 * (Delegated) Implementation of hook_civicrm_config
 */
function _<?php echo $mainFile ?>_civix_civicrm_config(&$config = NULL) {
  static $configured = FALSE;
  if ($configured) return;
  $configured = TRUE;

  $template =& CRM_Core_Smarty::singleton();

  $extRoot = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
  $extDir = $extRoot . 'templates';

  if ( is_array( $template->template_dir ) ) {
      array_unshift( $template->template_dir, $extDir );
  } else {
      $template->template_dir = array( $extDir, $template->template_dir );
  }

  $include_path = $extRoot . PATH_SEPARATOR . get_include_path( );
  set_include_path( $include_path );
}

/**
 * (Delegated) Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function _<?php echo $mainFile ?>_civix_civicrm_xmlMenu(&$files) {
  foreach (_<?php echo $mainFile ?>_civix_glob(__DIR__ . '/xml/Menu/*.xml') as $file) {
    $files[] = $file;
  }
}

/**
 * Implementation of hook_civicrm_install
 */
function _<?php echo $mainFile ?>_civix_civicrm_install() {
  _<?php echo $mainFile ?>_civix_civicrm_config();
  if ($upgrader = _<?php echo $mainFile ?>_civix_upgrader()) {
    return $upgrader->onInstall();
  }
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function _<?php echo $mainFile ?>_civix_civicrm_uninstall() {
  _<?php echo $mainFile ?>_civix_civicrm_config();
  if ($upgrader = _<?php echo $mainFile ?>_civix_upgrader()) {
    return $upgrader->onUninstall();
  }
}

/**
 * (Delegated) Implementation of hook_civicrm_enable
 */
function _<?php echo $mainFile ?>_civix_civicrm_enable() {
  _<?php echo $mainFile ?>_civix_civicrm_config();
  if ($upgrader = _<?php echo $mainFile ?>_civix_upgrader()) {
    if (is_callable(array($upgrader, 'onEnable'))) {
      return $upgrader->onEnable();
    }
  }
}

/**
 * (Delegated) Implementation of hook_civicrm_disable
 */
function _<?php echo $mainFile ?>_civix_civicrm_disable() {
  _<?php echo $mainFile ?>_civix_civicrm_config();
  if ($upgrader = _<?php echo $mainFile ?>_civix_upgrader()) {
    if (is_callable(array($upgrader, 'onDisable'))) {
      return $upgrader->onDisable();
    }
  }
}

/**
 * (Delegated) Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function _<?php echo $mainFile ?>_civix_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  if ($upgrader = _<?php echo $mainFile ?>_civix_upgrader()) {
    return $upgrader->onUpgrade($op, $queue);
  }
}

function _<?php echo $mainFile ?>_civix_upgrader() {
  if (!file_exists(__DIR__.'/<?php echo $namespace ?>/Upgrader.php')) {
    return NULL;
  } else {
    return <?php echo $_namespace ?>_Upgrader_Base::instance();
  }
}

/**
 * Search directory tree for files which match a glob pattern
 *
 * @param $dir string, base dir
 * @param $pattern string, glob pattern, eg "*.txt"
 * @return array(string)
 */
function _<?php echo $mainFile ?>_civix_find_files($dir, $pattern) {
  $todos = array($dir);
  $result = array();
  while (!empty($todos)) {
    $subdir = array_shift($todos);
    foreach (_<?php echo $mainFile ?>_civix_glob("$subdir/$pattern") as $match) {
      if (!is_dir($match)) {
        $result[] = $match;
      }
    }
    if ($dh = opendir($subdir)) {
      while (FALSE !== ($entry = readdir($dh))) {
        $path = $subdir . DIRECTORY_SEPARATOR . $entry;
        if ($entry == '.' || $entry == '..') {
        } elseif (is_dir($path)) {
          $todos[] = $path;
        }
      }
      closedir($dh);
    }
  }
  return $result;
}
/**
 * (Delegated) Implementation of hook_civicrm_managed
 *
 * Find any *.mgd.php files, merge their content, and return.
 */
function _<?php echo $mainFile ?>_civix_civicrm_managed(&$entities) {
  $mgdFiles = _<?php echo $mainFile ?>_civix_find_files(__DIR__, '*.mgd.php');
  foreach ($mgdFiles as $file) {
    $es = include $file;
    foreach ($es as $e) {
      if (empty($e['module'])) {
        $e['module'] = '<?php echo $fullName ?>';
      }
      $entities[] = $e;
    }
  }
}

/**
 * Glob wrapper which is guaranteed to return an array.
 *
 * The documentation for glob() says, "On some systems it is impossible to
 * distinguish between empty match and an error." Anecdotally, the return
 * result for an empty match is sometimes array() and sometimes FALSE.
 * This wrapper provides consistency.
 *
 * @see http://php.net/glob
 * @param string $pattern
 * @return array, possibly empty
 */
function _<?php echo $mainFile ?>_civix_glob($pattern) {
  $result = glob($pattern);
  return is_array($result) ? $result : array();
}
