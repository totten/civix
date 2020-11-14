<?php

use CRM_CivixBundle_Resources_Example_ExtensionUtil as E;

/**
 * Class CRM_CivixBundle_Resources_Example_UpgraderBase
 *
 * This is the de facto template for "CRM_*_Upgrader_Base" classes.
 *
 * Note: Unlike most civix classes, this lives in the global/top-level
 * namespace because that's a closer match to the actual output.
 */
class CRM_CivixBundle_Resources_Example_UpgraderBase {

  /**
   * @var CRM_CivixBundle_Resources_Example_UpgraderBase
   */
  public static $instance;

  /**
   * @var CRM_Queue_TaskContext
   */
  protected $ctx;

  /**
   * @var string
   *   eg 'com.example.myextension'
   */
  protected $extensionName;

  /**
   * @var string
   *   full path to the extension's source tree
   */
  protected $extensionDir;

  /**
   * @var array
   *   sorted numerically
   */
  private $revisions;

  /**
   * @var bool
   *   Flag to clean up extension revision data in civicrm_setting
   */
  private $revisionStorageIsDeprecated = FALSE;

  /**
   * Obtain a reference to the active upgrade handler.
   */
  public static function instance() {
    if (!self::$instance) {
      self::$instance = new static(E::LONG_NAME, E::path());
    }
    return self::$instance;
  }

  /**
   * Adapter that lets you add normal (non-static) member functions to the queue.
   *
   * Note: Each upgrader instance should only be associated with one
   * task-context; otherwise, this will be non-reentrant.
   *
   * ```
   * CRM_CivixBundle_Resources_Example_UpgraderBase::_queueAdapter($ctx, 'methodName', 'arg1', 'arg2');
   * ```
   */
  public static function _queueAdapter() {
    $instance = self::instance();
    $args = func_get_args();
    $instance->ctx = array_shift($args);
    $instance->queue = $instance->ctx->queue;
    $method = array_shift($args);
    return call_user_func_array([$instance, $method], $args);
  }

  /**
   * CRM_CivixBundle_Resources_Example_UpgraderBase constructor.
   *
   * @param $extensionName
   * @param $extensionDir
   */
  public function __construct($extensionName, $extensionDir) {
    $this->extensionName = $extensionName;
    $this->extensionDir = $extensionDir;
  }

  // ******** Task helpers ********

  // civix:inline_trait
  use CRM_CivixBundle_Resources_Example_SchemaBuilderTrait;

  // civix:inline_trait
  use CRM_CivixBundle_Resources_Example_UpgraderTasksTrait;

  /**
   * Syntactic sugar for enqueuing a task which calls a function in this class.
   *
   * The task is weighted so that it is processed
   * as part of the currently-pending revision.
   *
   * After passing the $funcName, you can also pass parameters that will go to
   * the function. Note that all params must be serializable.
   */
  public function addTask($title) {
    $args = func_get_args();
    $title = array_shift($args);
    $task = new CRM_Queue_Task(
      [get_class($this), '_queueAdapter'],
      $args,
      $title
    );
    return $this->queue->createItem($task, ['weight' => -1]);
  }

  // ******** Revision-tracking helpers ********

  // civix:inline_trait
  use CRM_CivixBundle_Resources_Example_UpgraderRevisionsTrait;

  // ******** Hook delegates ********

  /**
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
   */
  public function onInstall() {
    $pat = sprintf("xml/schema/%s/*.xml", str_replace('_', '/', E::CLASS_PREFIX));
    if (!empty(glob(E::path($pat)))) {
      $ctx = ['basedir' => E::path(), 'namespace' => E::CLASS_PREFIX, 'fullName' => E::LONG_NAME];
      $sql = $this->createSchemaBuilder($ctx)->addXml($pat)->generateSql('CREATE');
      CRM_Utils_File::runSqlQuery(CIVICRM_DSN, $sql);
    }

    $files = glob($this->extensionDir . '/sql/*_install.sql');
    if (is_array($files)) {
      foreach ($files as $file) {
        $this->executeSqlFile($file);
      }
    }
    $files = glob($this->extensionDir . '/sql/*_install.mysql.tpl');
    if (is_array($files)) {
      foreach ($files as $file) {
        $this->executeSqlTemplate($file);
      }
    }
    $files = glob($this->extensionDir . '/xml/*_install.xml');
    if (is_array($files)) {
      foreach ($files as $file) {
        $this->executeCustomDataFileByAbsPath($file);
      }
    }
    if (is_callable([$this, 'install'])) {
      $this->install();
    }
  }

  /**
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
   */
  public function onPostInstall() {
    $revisions = $this->getRevisions();
    if (!empty($revisions)) {
      $this->setCurrentRevision(max($revisions));
    }
    if (is_callable([$this, 'postInstall'])) {
      $this->postInstall();
    }
  }

  /**
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
   */
  public function onUninstall() {
    $files = glob($this->extensionDir . '/sql/*_uninstall.mysql.tpl');
    if (is_array($files)) {
      foreach ($files as $file) {
        $this->executeSqlTemplate($file);
      }
    }
    if (is_callable([$this, 'uninstall'])) {
      $this->uninstall();
    }
    $files = glob($this->extensionDir . '/sql/*_uninstall.sql');
    if (is_array($files)) {
      foreach ($files as $file) {
        CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $file);
      }
    }
  }

  /**
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
   */
  public function onEnable() {
    // stub for possible future use
    if (is_callable([$this, 'enable'])) {
      $this->enable();
    }
  }

  /**
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
   */
  public function onDisable() {
    // stub for possible future use
    if (is_callable([$this, 'disable'])) {
      $this->disable();
    }
  }

  public function onUpgrade($op, CRM_Queue_Queue $queue = NULL) {
    switch ($op) {
      case 'check':
        return [$this->hasPendingRevisions()];

      case 'enqueue':
        return $this->enqueuePendingRevisions($queue);

      default:
    }
  }

}
