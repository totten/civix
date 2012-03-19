<?php
echo "<?php\n";
?>

require_one 'CRM/Core/Page.php';

class <?= $namespace ?>_Page_<?= $pageClassName ?> extends CRM_Core_Page {
    function run() {
        $this->assign('currentTime', date('Y-m-d H:i:s'));
        parent::run();
    }
}
