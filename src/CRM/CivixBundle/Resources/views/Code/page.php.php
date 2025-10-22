<?php
echo "<?php\n";
?>

declare(strict_types = 1);
<?php
$_namespace = preg_replace(':/:', '_', $namespace);
?>

use <?php echo $_namespace ?>_ExtensionUtil as E;

class <?php echo preg_replace(':/:', '_', $fullClassName) ?> extends CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(E::ts('<?php echo $shortClassName ?>'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    parent::run();
  }

}
