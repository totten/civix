<?php
namespace CRM\CivixBundle\Builder;

use Civix;
use CRM\CivixBundle\Utils\Versioning;
use LicenseData\Repository;
use SimpleXMLElement;

/**
 * Build/update info.xml
 */
class Info extends XML {

  public function init(&$ctx) {
    $ctx += [
      // FIXME: Auto-detect current installed civi version
      'compatibilityVerMin' => 5.45,
    ];

    $xml = new SimpleXMLElement('<extension></extension>');
    $xml->addAttribute('key', $ctx['fullName']);
    $xml->addAttribute('type', $ctx['type']);
    // $xml->addChild('downloadUrl', 'http://FIXME/' . $ctx['fullName'] . '.zip');
    $xml->addChild('file', $ctx['mainFile']);
    $xml->addChild('name', $ctx['fullName']);
    $xml->addChild('description', 'FIXME');
    // urls
    $xml->addChild('license', $ctx['license'] ?? 'FIXME');
    $authors = $xml->addChild('authors');
    $maint = $authors->addChild('author');
    $maint->addChild('name', $ctx['author'] ?? 'FIXME');
    $maint->addChild('email', $ctx['email'] ?? 'FIXME@example.com');
    $maint->addChild('role', 'Maintainer');

    $urls = $xml->addChild('urls');
    $urls->addChild('url', 'http://FIXME')->addAttribute('desc', 'Main Extension Page');
    $urls->addChild('url', 'http://FIXME')->addAttribute('desc', 'Documentation');
    $urls->addChild('url', 'http://FIXME')->addAttribute('desc', 'Support');

    $licenses = new Repository();
    if (isset($ctx['license']) && $license = $licenses->get($ctx['license'])) {
      $urls->addChild('url', $license->getUrl())->addAttribute('desc', 'Licensing');
    }
    else {
      $urls->addChild('url', 'http://FIXME')->addAttribute('desc', 'Licensing');
    }

    $xml->addChild('releaseDate', date('Y-m-d'));
    $xml->addChild('version', '1.0');
    $xml->addChild('develStage', 'alpha');
    $xml->addChild('compatibility')->addChild('ver', $ctx['compatibilityVerMin']);
    $xml->addChild('comments', 'This is a new, undeveloped module');

    // APIv4 will look for classes+files matching 'Civi/Api4', and
    // classes for this ext should be 'Civi\MyExt', so this is the
    // simplest default.
    $classloader = $xml->addChild('classloader');

    $crmClassloaderRule = $classloader->addChild('psr0');
    $crmClassloaderRule->addAttribute('prefix', 'CRM_');
    $crmClassloaderRule->addAttribute('path', '.');

    $civiClassloaderRule = $classloader->addChild('psr4');
    $civiClassloaderRule->addAttribute('prefix', 'Civi\\');
    $civiClassloaderRule->addAttribute('path', 'Civi');

    // store extra metadata to facilitate code manipulation
    $civix = $xml->addChild('civix');
    if (isset($ctx['namespace'])) {
      $civix->addChild('namespace', $ctx['namespace']);
    }
    $civix->addChild('format', $ctx['civixFormat'] ?? Civix::upgradeList()->getHeadVersion());
    if (isset($ctx['angularModuleName'])) {
      $civix->addChild('angularModule', $ctx['angularModuleName']);
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
    $items = $this->get()->xpath('civix/angularModule');
    $angularModule = (string) array_shift($items);
    $ctx['angularModuleName'] = !empty($angularModule) ? $angularModule : $ctx['mainFile'];
    $items = $this->get()->xpath('civix/format');
    $ctx['civixFormat'] = (string) array_shift($items);
    $ctx['compatibilityVerMin'] = $this->getCompatibilityVer('MIN');
    $ctx['compatibilityVerMax'] = $this->getCompatibilityVer('MAX');
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
   * Get the extension's file name (short name).
   * @return string
   */
  public function getFile(): string {
    $items = $this->get()->xpath('file');
    return (string) array_shift($items);
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

  public function getExtensionUtilClass(): string {
    return str_replace('/', '_', $this->getNamespace()) . '_ExtensionUtil';
  }

  /**
   * Get the namespace into which civix should place files
   * @return string
   */
  public function getNamespace(): string {
    $items = $this->get()->xpath('civix/namespace');
    $result = (string) array_shift($items);
    if ($result) {
      return $result;
    }
    else {
      throw new \RuntimeException("Failed to lookup civix/namespace in info.xml");
    }
  }

  /**
   * Determine the target version of CiviCRM.
   *
   * @param string $mode
   *   The `info.xml` file may list multiple `<ver>` tags, and we will only return one.
   *   Either return the lowest-compatible `<ver>` ('MIN') or the highest-compatible `<ver>` ('MAX').
   * @return string|null
   */
  public function getCompatibilityVer(string $mode = 'MIN'): ?string {
    $vers = [];
    foreach ($this->get()->xpath('compatibility/ver') as $ver) {
      $vers[] = (string) $ver;
    }
    return Versioning::pickVer($vers, $mode);
  }

  /**
   * Increase the minimum requirement (`<compatibility><ver>X.Y</ver></compatibility>`).
   *
   * There may be multiple `<ver>` expressions. They will be reconciled as follows:
   * - Any existing constraints (which are < X.Y) will be dropped.
   * - Any existing constraints (which are >= X.Y) will be kept.
   * - If necessary, it will add a constraint for `X.Y`.
   *
   * @param string $newMin
   *   Ex: '5.27'
   */
  public function raiseCompatibilityMinimum(string $newMin): void {
    /** @var \SimpleXMLElement $xml */
    $xml = $this->get();
    $keptConstraints = 0;
    foreach ($xml->xpath('compatibility/ver') as $existingConstraint) {
      if (version_compare((string) $existingConstraint, $newMin, '>=')) {
        $keptConstraints++;
      }
      else {
        // dom_import_simplexml($existingConstraint)->remove();
        $dom = dom_import_simplexml($existingConstraint);
        $dom->parentNode->removeChild($dom);
      }
    }

    if (!$keptConstraints) {
      if (empty($xml->xpath('compatibility'))) {
        $xml->addChild('compatibility');
      }
      $xml->compatibility->addChild('ver', $newMin);
    }
  }

  /**
   * Determine the civix-format version of this extension.
   *
   * If the value isn't explicitly available, inspect some related fields to make an
   * educated  guess.
   *
   * @return string
   */
  public function detectFormat(): string {
    $items = $this->get()->xpath('civix/format');
    $explicit = (string) array_shift($items);
    if ($explicit) {
      return $explicit;
    }

    $mixins = $this->get()->xpath('mixins');
    return empty($mixins) ? '13.10.0' : '22.05.0';
  }

  public function getClassloaders(): array {
    $loaders = [];

    foreach (['psr0', 'psr4'] as $type) {
      $items = $this->get()->xpath("classloader/$type");
      foreach ($items as $item) {
        $attrs = $item->attributes();
        $loaders[] = [
          'type' => $type,
          'prefix' => (string) $attrs['prefix'],
          'path' => (string) $attrs['path'],
        ];
      }
    }
    return $loaders;
  }

  /**
   * @param array $loaders
   *   Ex: [['type' => 'psr4', 'prefix' => 'Civi\Foobar\', 'path' => 'src']]
   */
  public function setClassLoaders(array $loaders): void {
    foreach ($this->xml->xpath('classloader/*') as $child) {
      unset($child[0]);
    }

    $classloader = $this->findCreateElement($this->xml, 'classloader');
    foreach ($loaders as $loader) {
      $rule = $classloader->addChild($loader['type']);
      $rule->addAttribute('prefix', $loader['prefix']);
      $rule->addAttribute('path', $loader['path']);
    }
  }

  private function findCreateElement(SimpleXMLElement $base, string $tag): SimpleXMLElement {
    foreach ($base->xpath($tag) as $existingClassloader) {
      return $existingClassloader;
    }
    return $base->addChild($tag);
  }

}
