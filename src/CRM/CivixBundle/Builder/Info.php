<?php
namespace CRM\CivixBundle\Builder;

use SimpleXMLElement;
use CRM\CivixBundle\Builder\XML;

/**
 * Build/update info.xml
 */
class Info extends XML {

  public function init(&$ctx) {
    $xml = new SimpleXMLElement('<extension></extension>');
    $xml->addAttribute('key', $ctx['fullName']);
    $xml->addAttribute('type', $ctx['type']);
    // $xml->addChild('downloadUrl', 'http://FIXME/' . $ctx['fullName'] . '.zip');
    $xml->addChild('file', $ctx['mainFile']);
    $xml->addChild('name', 'FIXME');
    $xml->addChild('description', 'FIXME');
    // urls
    $xml->addChild('license', isset($ctx['license']) ? $ctx['license'] : 'FIXME');
    $maint = $xml->addChild('maintainer');
    $maint->addChild('author', isset($ctx['author']) ? $ctx['author'] : 'FIXME');
    $maint->addChild('email', isset($ctx['email']) ? $ctx['email'] : 'FIXME@example.com');

    $urls = $xml->addChild('urls');
    $urls->addChild('url', 'http://FIXME')->addAttribute('desc', 'Main Extension Page');
    $urls->addChild('url', 'http://FIXME')->addAttribute('desc', 'Documentation');
    $urls->addChild('url', 'http://FIXME')->addAttribute('desc', 'Support');

    $licenses = new \LicenseData\Repository();
    if (isset($ctx['license']) && $license = $licenses->get($ctx['license'])) {
      $urls->addChild('url', $license->getUrl())->addAttribute('desc', 'Licensing');
    }
    else {
      $urls->addChild('url', 'http://FIXME')->addAttribute('desc', 'Licensing');
    }

    $xml->addChild('releaseDate', date('Y-m-d'));
    $xml->addChild('version', '1.0');
    $xml->addChild('develStage', 'alpha');
    $xml->addChild('compatibility')->addChild('ver', '5.0');
    $xml->addChild('comments', 'This is a new, undeveloped module');

    // APIv4 will look for classes+files matching 'Civi/Api4', and
    // classes for this ext should be 'Civi\MyExt', so this is the
    // simplest default.
    $classloader = $xml->addChild('classloader');
    $classloaderRule = $classloader->addChild('psr4');
    $classloaderRule->addAttribute('prefix', 'Civi\\');
    $classloaderRule->addAttribute('path', 'Civi');

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

  public function load(&$ctx) {
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
  public function getKey() {
    $attrs = $this->get()->attributes();
    return (string) $attrs['key'];
  }

  /**
   * Get the extension type
   *
   * @return string (e.g. "module", "report")
   */
  public function getType() {
    $attrs = $this->get()->attributes();
    return (string) $attrs['type'];
  }

  /**
   * Get the user-friendly name of the extension.
   *
   * @return string
   */
  public function getExtensionName() {
    return empty($this->xml->name) ? 'FIXME' : $this->xml->name;
  }

}
