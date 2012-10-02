<?php
namespace CRM\CivixBundle\Builder;

use SimpleXMLElement;
use CRM\CivixBundle\Builder\XML;

/**
 * Build/update info.xml
 */
class Info extends XML {

    function init(&$ctx) {
        $xml = new SimpleXMLElement('<extension></extension>');
        $xml->addAttribute('key', $ctx['fullName']);
        $xml->addAttribute('type', $ctx['type']);
        // $xml->addChild('downloadUrl', 'http://FIXME/' . $ctx['fullName'] . '.zip');
        $xml->addChild('file', $ctx['mainFile']);
        $xml->addChild('name', 'FIXME');
        $xml->addChild('description', 'FIXME');
        // urls
        $xml->addChild('license', 'FIXME');
        $maint = $xml->addChild('maintainer');
        $maint->addChild('author', 'FIXME');
        $maint->addChild('email', 'FIXME@example.com');
        $xml->addChild('releaseDate', date('Y-m-d'));
        $xml->addChild('version', '1.0');
        $xml->addChild('develStage', 'alpha');
        $xml->addChild('compatibility')->addChild('ver', '4.2');
        $xml->addChild('comments', 'This is a new, undeveloped module');

        // store extra metadata to facilitate code manipulation
        $civix = $xml->addChild('civix');
        if (isset($ctx['namespace'])) {
            $civix->addChild('namespace', $ctx['namespace']);
        }

        if (isset($ctx['typeInfo'])) {
            $typeInfo = $xml->addChild('typeInfo');
            foreach ($ctx['typeInfo'] as $key => $value) {
                $typeInfo->addChild($key, $value);
            }
        }

        $this->set($xml);
    }

    function load(&$ctx) {
        parent::load($ctx);
        $attrs = $this->get()->attributes();
        $ctx['fullName'] = (string) $attrs['key'];
        $items = $this->get()->xpath('file');
        $ctx['mainFile'] = (string) array_shift($items);
        $items = $this->get()->xpath('civix/namespace');
        $ctx['namespace'] = (string) array_shift($items);
    }

    /**
     * Get the extension's full name
     *
     * @return string (e.g. "com.example.myextension)
     */
    function getKey() {
        $attrs = $this->get()->attributes();
        return (string) $attrs['key'];
    }

    /**
     * Get the extension type
     *
     * @return string (e.g. "module", "report")
     */
    function getType() {
        $attrs = $this->get()->attributes();
        return (string) $attrs['type'];
    }
}
