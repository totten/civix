<?php

#### Find primary autoloader
$autoloaders = array(
  implode(DIRECTORY_SEPARATOR, array(dirname(__DIR__), 'vendor', 'autoload.php')),
  implode(DIRECTORY_SEPARATOR, array(dirname(dirname(dirname(dirname(__DIR__)))), 'vendor', 'autoload.php')),
);
foreach ($autoloaders as $autoloader) {
  if (file_exists($autoloader)) {
    $loader = require_once $autoloader;
    break;
  }
}
if (!isset($loader)) {
  die("Failed to find autoloader");
}

#### Extra - Register classes in "tests" directory
$loader->addPsr4('E2E\\', __DIR__ . '/e2e');
