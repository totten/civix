<?php
echo "<?php\n";
$_namespace = preg_replace(':/:','_',$namespace);
?>

// AUTO-GENERATED FILE -- This may be overwritten!

/**
 * Base class which provides helpers to execute upgrade logic
 */
class <?= $_namespace ?>_Upgrader_Base {

  /**
   * @var varies, subclass of htis
   */
  static $instance;

  /**
   * @var CRM_Queue_TaskContext
   */
  protected $ctx;

  /**
   * @var string, eg 'com.example.myextension'
   */
  protected $extensionName;

  /**
   * @var string, full path to the extension's source tree
   */
  protected $extensionDir;

  /**
   * @var array(revisionNumber) sorted numerically
   */
  private $revisions;

  /**
   * Obtain a refernece to the active upgrade handler
   */
  static function instance() {
    if (! self::$instance) {
      // FIXME auto-generate
      self::$instance = new <?= $_namespace ?>_Upgrader(
        '<?= $fullName ?>',
        __DIR__ .'../../../'
      );
    }
    return self::$instance;
  }

  /**
   * Adapter that lets you add normal (non-static) member functions to the queue.
   *
   * Note: If multiple task-contexts exist, then this is non-reentrant.
   *
   * @code
   * <?= $_namespace ?>_Upgrader_Base::_queueAdapter($ctx, 'methodName', 'arg1', 'arg2');
   * @endcode
   */
  static function _queueAdapter() {
    $instance = self::instance();
    $args = func_get_args();
    $instance->ctx = array_shift($args);
    $instance->queue = $instance->ctx->queue;
    $method = array_shift($args);
    return call_user_func_array(array($instance, $method), $args);
  }

  function __construct($extensionName, $extensionDir) {
    $this->extensionName = $extensionName;
    $this->extensionDir = $extensionDir;
  }

  // ******** Task helpers ********

  /**
   * Run a SQL file
   */
  function executeSqlFile($relativePath) {
    CRM_Utils_File::sourceSQLFile(
      CIVICRM_DSN,
      $this->extensionDir . '/' . $relativePath
    );
    return TRUE;
  }

  /**
   * Run one SQL query
   *
   * This is just a wrapper for CRM_Core_DAO::executeSql, but it
   * provides syntatic sugar for queueing several tasks that
   * run different queries
   */
  function executeSql($query, $params = array()) {
    // FIXME verify that we raise an exception on error
    CRM_Core_DAO::executeSql($query, $params);
    return TRUE;
  }

  /**
   * Syntatic sugar for enqueuing a task which calls a function
   * in this class. The task is weighted so that it is processed
   * as part of the currently-pending revision.
   *
   * After passing the $funcName, you can also pass parameters that will go to
   * the function. Note that all params must be serializable.
   */
  function addTask($title) {
    $args = func_get_args();
    $title = array_shift($args);
    $task = new CRM_Queue_Task(
      array(get_class($this), '_queueAdapter'),
      $args,
      $title
    );
    return $this->queue->createItem($task, array('weight' => -1));
  }

  // ******** Revision-tracking helpers ********

  /**
   * Determine if there are any pending revisions
   *
   * @return bool
   */
  function hasPendingRevisions() {
    $revisions = $this->getRevisions();
    $currentRevision = $this->getCurrentRevision();

    if (empty($revisions)) {
      return FALSE;
    }
    if (empty($currentRevision)) {
      return TRUE;
    }

    return ($currentRevision < max($revisions));
  }

  /**
   * Add any pending revisions to the queue
   */
  function enqueuePendingRevisions(CRM_Queue_Queue $queue) {
    $this->queue = $queue;

    $currentRevision = $this->getCurrentRevision();
    foreach ($this->getRevisions() as $revision) {
      if ($revision > $currentRevision) {
        $title = ts('Upgrade %1 to revision %2', array(
          1 => $this->extensionName,
          2 => $revision,
        ));

        // note: don't use addTask() because it sets weight=-1

        $task = new CRM_Queue_Task(
          array(get_class($this), '_queueAdapter'),
          array('upgrade_' . $revision),
          $title
        );
        $this->queue->createItem($task);

        $task = new CRM_Queue_Task(
          array(get_class($this), '_queueAdapter'),
          array('setCurrentRevision', $revision),
          $title
        );
        $this->queue->createItem($task);
      }
    }
  }

  /**
   * Get a list of revisions
   *
   * @return array(revisionNumbers) sorted numerically
   */
  function getRevisions() {
    if (! is_array($this->revisions)) {
      $this->revisions = array();

      $clazz = new ReflectionClass(get_class($this));
      $methods = $clazz->getMethods();
      foreach ($methods as $method) {
        if (preg_match('/^upgrade_(.*)/', $method->name, $matches)) {
          $this->revisions[] = $matches[1];
        }
      }
      sort($this->revisions, SORT_NUMERIC);
    }

    return $this->revisions;
  }

  function getCurrentRevision() {
    return CRM_Core_BAO_Extension::getSchemaVersion($this->extensionName);
  }

  function setCurrentRevision($revision) {
    CRM_Core_BAO_Extension::setSchemaVersion($this->extensionName, $revision);
    return TRUE;
  }

}
