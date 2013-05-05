<?php
namespace CRM\CivixBundle\Builder;

use SimpleXMLElement;
use CRM\CivixBundle\Builder\XML;

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

    function addItem($ctx, $title, $fullClass, $path) {
        $item = $this->get()->addChild('item');
        $item->addChild('path', $path);
        $item->addChild('page_callback', $fullClass);
        $item->addChild('title', $title);
        $item->addChild('access_arguments', 'access CiviCRM');
    }
}
