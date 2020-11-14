<?php
echo "<?php\n";
$_namespace = preg_replace(':/:', '_', $namespace);
?>

// AUTO-GENERATED FILE -- Civix may overwrite any changes made to this file
use <?php echo $_namespace ?>_ExtensionUtil as E;

/**
 * Base class which provides helpers to execute upgrade logic
 */
<?php

function copyClass($inClass, $outClass) {
  $c = new \ReflectionClass($inClass);
  $code = file_get_contents($c->getFileName());
  $lines = explode("\n", $code);
  $code = implode("\n", array_slice($lines,
    $c->getStartLine() - 1,
    $c->getEndLine() - $c->getStartLine() + 1)
    ) . "\n";
  $code = str_replace($inClass, $outClass, $code);
  return $code;
}

echo copyClass(
  'CRM_CivixBundle_Resources_Example_UpgraderBase',
  $_namespace . '_Upgrader_Base'
);
