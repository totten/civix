<?php
echo "<?php\n";
?>

declare(strict_types = 1);
<?php
$_namespace = preg_replace(':/:', '_', $namespace);
?>

use <?php echo $_namespace ?>_ExtensionUtil as E;

final class <?php echo $baoClassName ?> extends <?php echo $daoClassName ?> {

}
