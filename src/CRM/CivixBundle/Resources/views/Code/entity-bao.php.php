<?php
echo "<?php\n";
$_namespace = preg_replace(':/:', '_', $namespace);
?>

use <?php echo $_namespace ?>_ExtensionUtil as E;

class <?php echo $baoClassName ?> extends <?php echo $daoClassName ?> {

}
