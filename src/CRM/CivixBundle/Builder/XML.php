<?php
namespace CRM\CivixBundle\Builder;

use SimpleXMLElement;
use DOMDocument;
use CRM\CivixBundle\Builder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build/update an XML file
 */
class XML implements Builder {

    protected $path, $xml;

    function __construct($path) {
        $this->path = $path;
    }

    /**
     * Get the xml document
     *
     * @return SimpleXMLElement
     */
    function get() {
        return $this->xml;
    }

    function set($xml) {
        $this->xml = $xml;
    }

    function loadInit(&$ctx) {
        if (file_exists($this->path)) {
            $this->load($ctx);
        } else {
            $this->init($ctx);
        }
    }

    /**
     * Initialize a new XML document
     */
    function init(&$ctx) {
    }

    /**
     * Read from file
     */
    function load(&$ctx) {
        $dom = new DomDocument( );
        $dom->load($this->path);
        $dom->xinclude( );
        $this->xml = simplexml_import_dom( $dom );
    }

    /**
     * Write the xml document
     */
    function save(&$ctx, OutputInterface $output) {
        $output->writeln("<info>Write " . $this->path . "</info>");

        // force pretty printing with encode/decode cycle
        $outXML = $this->get()->saveXML();
        $xml = new DOMDocument();
        $xml->encoding = 'iso-8859-1';
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;
        $xml->loadXML($outXML);
        file_put_contents($this->path, $xml->saveXML());
    }
}
