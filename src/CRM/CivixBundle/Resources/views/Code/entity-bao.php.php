<?php
echo "<?php\n";
$_namespace = preg_replace(':/:', '_', $namespace);
?>
// phpcs:disable
use <?php echo $_namespace ?>_ExtensionUtil as E;
// phpcs:enable

class <?php echo $baoClassName ?> extends <?php echo $daoClassName ?> {

}
