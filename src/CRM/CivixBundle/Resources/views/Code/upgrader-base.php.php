<?php
echo "<?php\n\n";
$_namespace = preg_replace(':/:', '_', $namespace);

echo \CRM\CivixBundle\Utils\ClassBuilder::create($_namespace . '_Upgrader_Base')
  ->setInClass('CRM_CivixBundle_Resources_Example_UpgraderBase')
  ->addComments([
    'Base class which provides helpers to execute upgrade logic',
    '',
    'AUTO-GENERATED FILE -- Civix may overwrite any changes made to this file',
  ])
  ->addUses(sprintf('use %s_ExtensionUtil as E;', $_namespace))
  ->toPHP();
