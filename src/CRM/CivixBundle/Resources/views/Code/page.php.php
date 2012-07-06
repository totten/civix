<?php
echo "<?php\n";
?>

require_once 'CRM/Core/Page.php';

class <?= preg_replace(':/:','_',$namespace) ?>_Page_<?= $pageClassName ?> extends CRM_Core_Page {
  function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('<?= $pageClassName ?>'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    parent::run();
  }
}
