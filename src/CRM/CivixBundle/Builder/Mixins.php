<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Builder;
use CRM\CivixBundle\Services;
use CRM\CivixBundle\Utils\Versioning;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Copy and rename a class.
 *
 * Note: Assumes PEAR-style (one class per file; nameDelimiter=_; no
 * namespace) and extremely distinctive class name (plain string
 * substitution - no PHP parsing)
 */
class Mixins implements Builder {

  /**
   * @var \CRM\CivixBundle\Builder\Info
   */
  protected $info;

  /**
   * @var string
   */
  protected $outputDir;

  /**
   * @var string[]
   */
  protected $newConstraints;

  /**
   * @var string[]
   */
  protected $removals;

  /**
   * @var array
   */
  protected $allBackports;

  /**
   * Mixins constructor.
   *
   * @param Info $info
   * @param string $outputDir
   * @param string[]|string $newConstraints
   *   List of constraints to be added or updated.
   *   Ex: ['foo@1.1']
   */
  public function __construct(Info $info, $outputDir, $newConstraints = []) {
    $this->info = $info;
    $this->outputDir = $outputDir;
    $this->newConstraints = (array) $newConstraints;
    $this->removals = [];
    $this->allBackports = Services::mixinBackports();
  }

  public function loadInit(&$ctx) {
  }

  public function init(&$ctx) {
  }

  public function load(&$ctx) {
  }

  public function addMixin(string $newMixinConstraint) {
    $this->newConstraints[] = $newMixinConstraint;
  }

  public function removeMixin(string $mixinNameOrConstraint) {
    [$mixinName] = explode('@', $mixinNameOrConstraint);
    $this->removals[] = $mixinName;
  }

  public function removeAllMixins() {
    foreach ($this->info->get()->xpath('mixins/mixin') as $x) {
      $this->removeMixin((string) $x);
    }
    foreach ($this->getMixinFiles() as $mixinFile) {
      $this->removeMixin($mixinFile);
    }
  }

  protected function addMixinToXml(string $newMixinConstraint) {
    [$newMixinName, $newMixinVer] = explode('@', $newMixinConstraint);
    // Just write the major version since that's all core will care about
    [$majorVersion] = explode('.', $newMixinVer);
    $newMixinConstraint = $newMixinName . '@' . $majorVersion;

    /** @var \SimpleXMLElement $xml */
    $xml = $this->info->get();

    foreach ($xml->xpath('mixins/mixin') as $existingMixinXml) {
      [$name, $ver] = explode('@', (string) $existingMixinXml);
      if ($name === $newMixinName) {
        $existingMixinXml[0] = $newMixinConstraint;
        return $this;
      }
    }

    if (empty($xml->xpath('mixins'))) {
      $xml->addChild('mixins');
    }

    foreach ($xml->xpath('mixins') as $mixinsXml) {
      $mixinsXml->addChild('mixin', $newMixinConstraint);
    }

    return $this;
  }

  /**
   * Write the xml document
   */
  public function save(&$ctx, OutputInterface $output) {
    foreach ($this->removals as $removedMixin) {
      $nodes = $this->info->get()->xpath('mixins/mixin[starts-with(text(), "' . $removedMixin . '@")]');
      foreach ($nodes as $existingMixinXml) {
        $output->writeln("<info>Unregister</info> " . $existingMixinXml);
        unset($existingMixinXml[0]);
      }
    }

    // Let's clarify the versions we want.
    $actualDeclarations = array_merge($this->getDeclaredMixinConstraints(), $this->newConstraints);
    $expectedDeclarations = array_column(Services::mixlib()->resolve($actualDeclarations), 'mixinConstraint');
    foreach ($expectedDeclarations as $newMixin) {
      $this->addMixinToXml($newMixin);
    }

    $this->reconcileMinimums($output);
    $this->reconcileBackports($output);
  }

  /**
   * Look at the list of `<mixin>` tags, and look at the list of `mixin/*.php` files.
   * Do we need to add or remove any  `mixin/*.php` files?
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  protected function reconcileBackports(OutputInterface $output) {
    $declarations = $this->getDeclaredMixinConstraints();
    $declarations[] = 'polyfill';

    $expectedBackports = array_filter($declarations, function ($d) {
      return !$this->isProvidedByCore($d);
    });
    $existingBackports = $this->getMixinFiles(TRUE);
    $missingBackports = array_diff($expectedBackports, $existingBackports);
    $extraBackports = array_diff($existingBackports, $expectedBackports);

    foreach ($missingBackports as $mixinName) {
      $mixinSpec = Services::mixlib()->get($mixinName);
      $this->createBackportFile($output, $mixinSpec);
    }

    foreach ($extraBackports as $extra) {
      $fileExt = ($extra === 'polyfill') ? '.php' : '.mixin.php';
      $file = $this->outputDir . '/' . $extra . $fileExt;
      $backportInfo = $this->getBackportInfo($extra);
      if ($this->isProvidedByCore($extra)) {
        $this->removeBackportFile($output, $file);
      }
      elseif ($backportInfo && !in_array($extra, $declarations)) {
        $this->removeBackportFile($output, $file);
      }
      else {
        $output->writeln("<error>Irregular mixin: \"$file\" is in the \"mixins/\" folder but is not referenced by \"info.xml\"! Consider deleting it.</error>");
        // Polyfill-loaders are likely to load the file even if it's not mentioned in info.xml. This old+new runtimes disagree about whether load $file.
      }
    }
  }

  protected function createBackportFile(OutputInterface $output, array $mixin) {
    $file = $this->outputDir . '/' . $mixin['mixinFile'];
    if (!is_dir(dirname($file))) {
      mkdir(dirname($file), Dirs::MODE, TRUE);
    }
    $output->writeln("<info>Write</info> $file");
    file_put_contents($file, $mixin['src']);
  }

  protected function removeBackportFile(OutputInterface $output, string $file): void {
    if (file_exists($file)) {
      $output->writeln("<info>Remove</info> $file");
      unlink($file);
    }
  }

  protected function getMixinFiles(bool $includePolyfill = FALSE): array {
    $filePattern = $this->outputDir . '/*.mixin.php';
    $result = array_map(
      function ($f) {
        return basename($f, '.mixin.php');
      },
      (array) glob($filePattern)
    );
    if ($includePolyfill && file_exists($this->outputDir . '/polyfill.php')) {
      $result[] = 'polyfill';
    }
    return $result;
  }

  protected function getBackportInfo(string $mixinConstraint): array {
    $mixinMajor = preg_replace('/^([^@]+@\d+)(\..*)/', '\1', $mixinConstraint);
    return $this->allBackports[$mixinMajor] ?? [];
  }

  protected function isProvidedByCore(string $mixinConstraint): bool {
    $compatVer = $this->info->getCompatibilityVer('MIN');
    $backportInfo = $this->getBackportInfo($mixinConstraint);
    $result = empty($backportInfo['provided-by']) ? FALSE : version_compare($compatVer, $backportInfo['provided-by'], '>=');
    // print_r([__FUNCTION__, '$compatVer'=> $compatVer, '$mixinConstraint' => $mixinConstraint, '$backportInfo' => $backportInfo, $result ? 'true' : 'false']);
    return $result;
  }

  protected function reconcileMinimums(OutputInterface $output): void {
    $currentMin = $this->info->getCompatibilityVer();
    $unmetMinimums = $this->findUnmetMinimums($this->getDeclaredMixinConstraints());
    if ($unmetMinimums) {
      $effectiveMin = Versioning::pickVer($unmetMinimums, 'MAX');

      foreach ($unmetMinimums as $mixinConstraint => $mixinMin) {
        $output->writeln("<comment>! [NOTE] The requirements should be increased because \"$mixinConstraint\" requires CiviCRM $mixinMin.</comment>");
      }
      $output->writeln("<info>Increase minimum CiviCRM requirement from</info> $currentMin <info>to</info> $effectiveMin");
      $this->info->raiseCompatibilityMinimum($effectiveMin);
    }
  }

  /**
   * Each required mixin may implicitly require some minimum version of Civi.
   * Does the current `<compatibility>` support these minimums?
   *
   * @param array $mixinConstraints
   *   Ex: ['entity-types-php@1.0.0', 'mgd-php@1.0.0', 'ang-php@1.0.0']
   * @return array
   *   Ex: ['entity-types-php@1.0.0' => '5.45']
   */
  protected function findUnmetMinimums(array $mixinConstraints): array {
    $currentMin = $this->info->getCompatibilityVer();
    $result = [];
    foreach ($mixinConstraints as $mixinConstraint) {
      $info = $this->getBackportInfo($mixinConstraint);
      if (isset($info['minimum']) && version_compare($currentMin, $info['minimum'], '<')) {
        $result[$mixinConstraint] = $info['minimum'];
      }
    }
    return $result;
  }

  /**
   * Get a list of mixin constraints (from info.xml).
   *
   * @return string[]
   *   Ex: ['foo@1.0', 'bar@2.0']
   */
  public function getDeclaredMixinConstraints(): array {
    $items = $this->info->get()->xpath('mixins/mixin');
    $mixins = [];
    foreach ($items as $item) {
      if ((string) $item) {
        $mixins[] = (string) $item;
      }
    }
    return $mixins;
  }

}
