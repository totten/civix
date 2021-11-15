<?php
namespace CRM\CivixBundle\Builder;

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

      $files = (array) glob($this->outputDir . '/' . $removedMixin . '@*.mixin.php');
      foreach ($files as $file) {
        $output->writeln("<info>Remove</info> $file");
        unlink($file);
      }
    }

    // Let's clarify the versions we want.
    $declared = array_merge($this->getDeclaredMixinConstraints(), $this->newConstraints);
    $expected = array_column(Services::mixlib()->resolve($declared), 'mixinConstraint');
    $existing = $this->getMixinFiles();

    $missing = array_diff($expected, $existing);
    $extras = array_diff($existing, $expected);

    foreach ($expected as $newMixin) {
      $this->addMixinToXml($newMixin);
    }

    $this->createLocalFile($output, Services::mixlib()->get('polyfill'));

    foreach ($missing as $mixinName) {
      $mixinSpec = Services::mixlib()->get($mixinName);
      $this->createLocalFile($output, $mixinSpec);
    }

    foreach ($extras as $extra) {
      $file = $this->outputDir . '/' . $extra . '.mixin.php';
      $output->writeln("<error>Extraneous file: \"$file\" appears to be obsolete. Please review \"info.xml\" and \"mixins/*\", and then remove the extra file.</error>");
      // Polyfill-loaders are likely to load the file even if it's not mentioned in info.xml.
    }
  }

  protected function createLocalFile(OutputInterface $output, array $mixin) {
    $file = $this->outputDir . '/' . $mixin['mixinFile'];
    if (!is_dir(dirname($file))) {
      mkdir(dirname($file), Dirs::MODE, TRUE);
    }
    $output->writeln("<info>Write</info> $file");
    file_put_contents($file, $mixin['src']);
  }

  protected function getMixinFiles(): array {
    $filePattern = $this->outputDir . '/*.mixin.php';
    return array_map(
      function ($f) {
        return str_replace('.mixin.php', '', basename($f));
      },
      (array) glob($filePattern)
    );
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
