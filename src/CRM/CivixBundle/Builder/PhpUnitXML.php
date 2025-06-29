<?php
namespace CRM\CivixBundle\Builder;

use SimpleXMLElement;

/**
 * Build phpunit.xml.dist
 */
class PhpUnitXML extends XML {

  public function init(&$ctx) {
    $xml = new SimpleXMLElement('<phpunit></phpunit>');
    $xml->addAttribute('backupGlobals', 'false');
    $xml->addAttribute('backupStaticAttributes', 'false');
    $xml->addAttribute('colors', 'true');
    $xml->addAttribute('convertErrorsToExceptions', 'true');
    $xml->addAttribute('convertNoticesToExceptions', 'true');
    $xml->addAttribute('convertWarningsToExceptions', 'true');
    $xml->addAttribute('convertDeprecationsToExceptions', 'true');
    $xml->addAttribute('processIsolation', 'false');
    $xml->addAttribute('stopOnFailure', 'false');
    $xml->addAttribute('cacheResult', 'false');
    $xml->addAttribute('bootstrap', 'tests/phpunit/bootstrap.php');

    $xml->registerXPathNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $xml->addAttribute('xsi:noNamespaceSchemaLocation', 'https://schema.phpunit.de/9.3/phpunit.xsd', 'http://www.w3.org/2001/XMLSchema-instance');

    $this->set($xml);

    $this->get()
      ->addChild('coverage')
      ->addChild('include')
      ->addChild('directory', './')
      ->addAttribute('suffix', '.php');

    $listenerXml = $this->get()->addChild('listeners')->addChild('listener');
    $listenerXml->addAttribute('class', 'Civi\\Test\\CiviTestListener');
    $listenerXml->addChild('arguments', '');
  }

  /**
   * @param string $name
   * @param array $dirs
   */
  public function addTestSuite($name, $dirs) {
    $testsuites = $this->get()->addChild('testsuites'); /* FIXME: find/load */
    $testsuite = $testsuites->addChild('testsuite');
    $testsuite->addAttribute('name', $name);
    foreach ($dirs as $dir) {
      $testsuite->addChild('directory', $dir);
    }
  }

}
