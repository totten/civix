<?php
namespace CRM\CivixBundle\Builder;

use SimpleXMLElement;
use CRM\CivixBundle\Builder\XML;

/**
 * Build/update info.xml
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
    $xml->addAttribute('processIsolation', 'false');
    $xml->addAttribute('stopOnFailure', 'false');
    $xml->addAttribute('bootstrap', 'tests/phpunit/bootstrap.php');
    $this->set($xml);

    $this->addTestSuite('My Test Suite', ['./tests/phpunit']);

    $this->get()
      ->addChild('filter')
      ->addChild('whitelist')
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
    $testsuites = $this->get()->addChild('testsuites'); // FIXME: find/load
    $testsuite = $testsuites->addChild('testsuite');
    $testsuite->addAttribute('name', $name);
    foreach ($dirs as $dir) {
      $testsuite->addChild('directory', $dir);
    }
  }

}
