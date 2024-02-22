<?php

namespace CRM\CivixBundle;

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
    return $this->coreVersionIs('>=', '5.73.beta1');
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

}
