<?php

use CRM_CivixBundle_Resources_Example_ExtensionUtil as E;

trait CRM_CivixBundle_Resources_Example_UpgraderRevisionsTrait {

  /**
   * Determine if there are any pending revisions.
   *
   * @return bool
   */
  public function hasPendingRevisions() {
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
   * Add any pending revisions to the queue.
   *
   * @param CRM_Queue_Queue $queue
   */
  public function enqueuePendingRevisions(CRM_Queue_Queue $queue) {
    $this->queue = $queue;

    $currentRevision = $this->getCurrentRevision();
    foreach ($this->getRevisions() as $revision) {
      if ($revision > $currentRevision) {
        $title = E::ts('Upgrade %1 to revision %2', [
          1 => $this->extensionName,
          2 => $revision,
        ]);

        // note: don't use addTask() because it sets weight=-1

        $task = new CRM_Queue_Task(
          [get_class($this), '_queueAdapter'],
          ['upgrade_' . $revision],
          $title
        );
        $this->queue->createItem($task);

        $task = new CRM_Queue_Task(
          [get_class($this), '_queueAdapter'],
          ['setCurrentRevision', $revision],
          $title
        );
        $this->queue->createItem($task);
      }
    }
  }

  /**
   * Get a list of revisions.
   *
   * @return array
   *   revisionNumbers sorted numerically
   */
  public function getRevisions() {
    if (!is_array($this->revisions)) {
      $this->revisions = [];

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

  public function getCurrentRevision() {
    $revision = CRM_Core_BAO_Extension::getSchemaVersion($this->extensionName);
    if (!$revision) {
      $revision = $this->getCurrentRevisionDeprecated();
    }
    return $revision;
  }

  private function getCurrentRevisionDeprecated() {
    $key = $this->extensionName . ':version';
    if ($revision = \Civi::settings()->get($key)) {
      $this->revisionStorageIsDeprecated = TRUE;
    }
    return $revision;
  }

  public function setCurrentRevision($revision) {
    CRM_Core_BAO_Extension::setSchemaVersion($this->extensionName, $revision);
    // clean up legacy schema version store (CRM-19252)
    $this->deleteDeprecatedRevision();
    return TRUE;
  }

  private function deleteDeprecatedRevision() {
    if ($this->revisionStorageIsDeprecated) {
      $setting = new CRM_Core_BAO_Setting();
      $setting->name = $this->extensionName . ':version';
      $setting->delete();
      CRM_Core_Error::debug_log_message("Migrated extension schema revision ID for {$this->extensionName} from civicrm_setting (deprecated) to civicrm_extension.\n");
    }
  }

}
