<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Application;
use CRM\CivixBundle\Builder;
use CRM\CivixBundle\Services;
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
    $this->allBackports = require Application::findCivixDir() . '/mixin-backports.php';
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
        $output->writeln("<error>Extra backport: \"$extra\" is already included with civicrm-core ({$backportInfo['provided-by']} >= {$this->info->getCompatibilityVer('MIN')}).</error>");
        $this->removeBackportFile($output, $file);
      }
      elseif ($backportInfo && !in_array($extra, $declarations)) {
        $output->writeln("<error>Extra backport: \"$extra\" is inactive. It can be removed</error>");
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
        return str_replace('.mixin.php', '', basename($f));
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
