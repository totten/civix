<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Utils\Files;
use SimpleXMLElement;
use DOMDocument;
use CRM\CivixBundle\Builder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build/update an XML file
 */
class XML implements Builder {

  protected $path, $xml;

  public function __construct($path) {
    $this->path = $path;
  }

  /**
   * Get the xml document
   *
   * @return SimpleXMLElement
   */
  public function get() {
    return $this->xml;
  }

  public function set($xml) {
    $this->xml = $xml;
  }

  public function loadInit(&$ctx) {
    if (file_exists($this->path)) {
      $this->load($ctx);
    }
    else {
      $this->init($ctx);
    }
  }

  /**
   * Initialize a new XML document
   */
  public function init(&$ctx) {
  }

  /**
   * Read from file
   */
  public function load(&$ctx) {
    $dom = new DomDocument();
    $dom->load($this->path);
    $dom->xinclude();
    $this->xml = simplexml_import_dom($dom);
  }

  /**
   * Write the xml document
   */
  public function save(&$ctx, OutputInterface $output) {
    $output->writeln("<info>Write</info> " . Files::relativize($this->path));

    // force pretty printing with encode/decode cycle
    $outXML = $this->get()->saveXML();
    $xml = new DOMDocument();
    $xml->encoding = 'iso-8859-1';
    $xml->preserveWhiteSpace = FALSE;
    $xml->formatOutput = TRUE;
    $xml->loadXML($outXML);
    file_put_contents($this->path, $xml->saveXML());
  }

}
