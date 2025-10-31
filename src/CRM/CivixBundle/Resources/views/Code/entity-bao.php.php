<?php
echo "<?php\n";
echo "declare(strict_types = 1);\n";
$_namespace = preg_replace(':/:', '_', $namespace);
?>

use <?php echo $_namespace ?>_ExtensionUtil as E;

final class <?php echo $baoClassName ?> extends <?php echo $daoClassName ?> {

}
