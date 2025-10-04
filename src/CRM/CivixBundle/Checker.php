<?php

namespace CRM\CivixBundle;

use CRM\CivixBundle\Builder\Mixins;
use CRM\CivixBundle\Parse\PrimitiveFunctionVisitor;
use CRM\CivixBundle\Utils\Files;

/**
 * This is a random grab-bag of conditionals that are show up when deciding how to generate code.
 */
class Checker {

  protected $generator;

  public function __construct(Generator $generator) {
    $this->generator = $generator;
  }

  /**
   * Check if the compatibility-target is greater than or less than X.
   *
   * Ex: '$this->isCoreVersion('<', '5.38.beta1')`
   *
   * @param string $op
   *   Ex: '<'
   * @param string $version
   *   '5.38.beta1'
   * @return bool
   *   TRUE if this extension targets a version less than 5.38.beta1.
   */
  public function coreVersionIs(string $op, string $version): bool {
    $compatibility = $this->generator->infoXml->getCompatibilityVer() ?: '5.0';
    return version_compare($compatibility, $version, $op);
  }

  /**
   * Does CiviCRM include our preferred version of PathLoad?
   *
   * @return bool
   */
  public function coreHasPathload(): bool {
    return $this->coreVersionIs('>=', '5.74.beta1');
  }

  /**
   * Determine if a mixin-library is already provided by civicrm-core.
   *
   * @param string $majorName
   * @return bool
   */
  public function coreProvidesLibrary(string $majorName): bool {
    if (!preg_match(';^civimix-;', $majorName)) {
      return FALSE;
    }

    // What version are we bundling into civix?
    $avail = $this->generator->mixinLibraries->available[$majorName] ?? NULL;
    if (!$avail) {
      throw new \RuntimeException("Unrecognized library: $majorName");
    }

    // civimix-* libraries track the version#s in core.
    // If core is v5.78, and if we want library v5.75, then core already provides.
    // If core is v5.63, and if we want library v5.75, then our version is required.
    return $this->coreVersionIs('>=', $avail->version);
  }

  /**
   * @param string $pattern
   *   Regex.
   * @return bool
   *   TRUE if the upgrader exists and matches the expression.
   */
  public function hasUpgrader(string $pattern = '/.+/'): bool {
    $upgrader = $this->generator->infoXml->get()->upgrader;
    return $upgrader && preg_match($pattern, $upgrader);
  }

  /**
   * @param string $pattern
   *   Regex.
   * @return bool
   *   TRUE if any mixin constraint matches the regex.
   */
  public function hasMixin(string $pattern = '/.+/'): bool {
    $mixins = new Mixins($this->generator->infoXml, $this->generator->baseDir->string('mixin'));
    $declared = $mixins->getDeclaredMixinConstraints();
    return !empty(preg_grep($pattern, $declared));
  }

  /**
   * Determine if this extension bundles-in a mixin-library.
   *
   * @param string $majorName
   *   Either major-name or a wildcard.
   *   Ex: 'civimix-schema@5' or '*'
   * @return bool
   */
  public function hasMixinLibrary(string $majorName = '*'): bool {
    return $this->generator->mixinLibraries->hasActive($majorName);
  }

  /**
   * Determine defines any schema/entities using `schema/*.entityType.php`.
   *
   * @return bool
   */
  public function hasSchemaPhp(): bool {
    $files = is_dir(\Civix::extDir('schema')) && \Civix::extDir()->search('glob:schema/*.entityType.php');
    return !empty($files);
  }

  public function getMyGlobalFunctions(): array {
    $names = [];
    foreach (['.php', '.civix.php'] as $suffix) {
      $file = $this->generator->baseDir->string($this->generator->infoXml->getFile() . $suffix);
      if (file_exists($file)) {
        $content = file_get_contents($file);
        $names = array_merge($names, PrimitiveFunctionVisitor::getAllNames($content));
      }
    }
    return $names;
  }

  /**
   * Determine if we have any classes some parent class.
   *
   * @param string $pattern
   *   Ex: "CRM_Foo_"
   * @return bool
   */
  public function hasSubclassesOf(string $pattern): bool {
    return !empty($this->grep(';extends[ \r\n\t]+' . $pattern . ';', ['CRM', 'Civi'], '*.php'));
  }

  /**
   * Search the source tree for a regex.
   *
   * @param string $bodyPattern
   *   Search for files which match this pattern.
   * @param array $dirs
   *   List of relative to search. Ex: ['CRM', 'Civi']
   * @param string $wildcard
   *   File-extension to search. Ex: '*.php'
   * @return array
   */
  public function grep(string $bodyPattern, array $dirs, string $wildcard): array {
    $matches = [];
    foreach ($dirs as $dir) {
      $files = Files::findFiles(\Civix::extDir($dir), $wildcard);
      $matches = array_merge($matches, Files::grepFiles($bodyPattern, $files));
    }
    return $matches;
  }

  /**
   * Determine if we have a declared <requires> for another extension
   *
   * @param string $requiredExt
   * @return bool
   */
  public function hasRequirement(string $requiredExt): bool {
    return in_array($requiredExt, $this->generator->infoXml->getRequiredExtensions());
  }

}
