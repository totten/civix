<?php

namespace CRM\CivixBundle\Utils;

class Versioning {

  /**
   * @param array $versions
   *   List of versions. Ex: ['4.7', '5.40', '5.41']
   * @param string $mode
   *   Either return the lowest version ('MIN') or the highest version ('MAX').
   * @return string|null
   */
  public static function pickVer(array $versions, string $mode): ?string {
    usort($versions, 'version_compare');

    switch ($mode) {
      case 'MIN':
        return $versions ? reset($versions) : NULL;

      case 'MAX':
        return $versions ? end($versions) : NULL;

      default:
        throw new \RuntimeException("pickVer($mode): Unrecognized mode");
    }
  }

}
