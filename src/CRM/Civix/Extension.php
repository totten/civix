<?php
namespace CRM\Civix;

use SimpleXMLElement;
use DOMDocument;
use Exception;

/**
 * Represent an extension which is produced by civix
 */
class Extension {
    static function load($xmlFile) {
        $dom = new DomDocument( );
        $dom->load( $xmlFile );
        $dom->xinclude( );
        $xml = simplexml_import_dom( $dom );

        $ext = new Exension();
        $ext->setXml($xml);
        return $ext;
    }
    
    function __construct($fullName, $mainFile, $namespace, $type, $basedir, $xml = NULL) {
        if ($type != 'module') {
            throw new Exception('FIXME: Untested extension type');
        }
        $this->basedir = $basedir;
        $this->fullName = $fullName;
        $this->mainFile = $mainFile;
        $this->namespace = $namespace;
        $this->type = $type;
        if ($xml) {
            $this->xml = $xml;
        } else {
            $this->xml = $this->createXml();
        }
    }
    
    function getDirs() {
        $dirs = array();
        $dirs[] = $this->basedir;
        $dirs[] = $this->basedir . DIRECTORY_SEPARATOR . 'templates';
        $dirs[] = $this->basedir . DIRECTORY_SEPARATOR . 'xml';
        $dirs[] = $this->basedir . DIRECTORY_SEPARATOR . $this->namespace;
        $dirs[] = $this->basedir . DIRECTORY_SEPARATOR . $this->namespace . DIRECTORY_SEPARATOR . 'BAO';
        $dirs[] = $this->basedir . DIRECTORY_SEPARATOR . $this->namespace . DIRECTORY_SEPARATOR . 'DAO';
        $dirs[] = $this->basedir . DIRECTORY_SEPARATOR . $this->namespace . DIRECTORY_SEPARATOR . 'Form';
        $dirs[] = $this->basedir . DIRECTORY_SEPARATOR . $this->namespace . DIRECTORY_SEPARATOR . 'Page';
        return $dirs;
    }
    
    /**
     * Initialize a new info.xml document
     *
     * @return SimpleXMLElement
     */
    protected function createXml() {
        $xml = new SimpleXMLElement('<extension></extension>');
        $xml->addAttribute('key', $this->fullName);
        $xml->addAttribute('type', $this->type);
        $xml->addChild('downloadUrl', 'http://FIXME/' . $this->fullName . '-1.0.zip');
        $xml->addChild('file', $this->mainFile);
        $xml->addChild('name', 'FIXME');
        $xml->addChild('description', 'FIXME');
        // urls
        $xml->addChild('license', 'FIXME');
        // maintainer
        $xml->addChild('releaseDate', date('Y-m-d'));
        $xml->addChild('version', '1.0');
        $xml->addChild('develStage', 'alpha');
        $xml->addChild('compatibility')->addChild('ver', '4.2');
        return $xml;
/*'
            <?xml version="1.0" encoding="iso-8859-1" ?>
 <extension key="org.civicrm.module.cividiscount" type="module">
  <downloadUrl>http://github.com/lobo/org.civicrm.module.cividiscount.zip</downloadUrl>
  <file>cividiscount</file>
  <name>CiviDiscount Module Extension</name>
  <description>desc</description>
  <urls>
    <url desc="Main Extension Page">http://civicrm.org</url>
    <url desc="Documentation">http://wiki.civicrm.org/confluence/display/CRMDOC/</url>
    <url desc="Support">http://forum.civicrm.org</url>
    <url desc="Licensing">http://civicrm.org/licensing</url>
  </urls>
  <license>AGPL</license>
  <maintainer>
    <author>CiviCRM LLC</author>
    <email>info@civicrm.org</email>
  </maintainer>
  <releaseDate>2012-04-01</releaseDate>
  <version>1.0</version>
  <develStage>beta</develStage>
  <compatibility>
    <ver>4.1</ver>
  </compatibility>
  <comments>For support, please contact project team on the forums. (http://forum.civicrm.org)</comments>
</extension>
            ');*/
    }
    
    /**
     * Get the info.xml document
     *
     * @return SimpleXMLElement
     */
    function getXml() {
        return $this->xml;
    }
    
    /**
     * Write info.xml document
     */
    function saveXml() {
        $dom = dom_import_simplexml($this->getXml())->ownerDocument;
        $dom->encoding = 'iso-8859-1';
        $dom->formatOutput = true;
        file_put_contents($this->basedir . DIRECTORY_SEPARATOR . 'info.xml', $dom->saveXML());
        
    }
}
