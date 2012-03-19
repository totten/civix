<?php
namespace CRM\Civix\Builder;

use SimpleXMLElement;
use CRM\Civix\Builder\XML;

/**
 * Build/update menu xml file
 */
class Menu extends XML {
    
    function init(&$ctx) {
        $xml = new SimpleXMLElement('<menu></menu>');
        $this->set($xml);
    }
    
    function hasPath($path) {
        $items = $this->get()->xpath(sprintf('item[path="%s"]', $path));
        return !empty($items);
    }
    
    function addItem($ctx, $className, $path) {
        $fullClass = implode('_', array($ctx['namespace'], 'Page', $className));
        $item = $this->get()->addChild('item');
        $item->addChild('path', $path);
        $item->addChild('page_callback', $fullClass);
        $item->addChild('title', $className);
        $item->addChild('access_arguments', 'access CiviCRM');
    }
}
