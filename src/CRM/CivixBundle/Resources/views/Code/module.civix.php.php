<?php
echo "<?php\n";
$_namespace = preg_replace(':/:','_',$namespace);
?>

// AUTO-GENERATED FILE -- This may be overwritten!

/**
 * (Delegated) Implementation of hook_civicrm_config
 */
function _<?= $mainFile ?>_civix_civicrm_config(&$config) {
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
function _<?= $mainFile ?>_civix_civicrm_xmlMenu(&$files) {
  foreach (glob(__DIR__ . '/xml/Menu/*.xml') as $file) {
    $files[] = $file;
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
function _<?= $mainFile ?>_civix_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  if (!file_exists(__DIR__.'/<?= $namespace ?>/Upgrader.php')) {
    return;
  }

  $instance = <?= $_namespace ?>_Upgrader_Base::instance();
  switch($op) {
    case 'check':
      return array($instance->hasPendingRevisions());
    case 'enqueue':
      return $instance->enqueuePendingRevisions($queue);
    default:
  }
}
