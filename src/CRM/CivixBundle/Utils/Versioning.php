<?php

namespace CRM\CivixBundle\Utils;

class Versioning {

  /**
   * Get earliest secure version of CiviCRM
   * @return string|null
   */
  public static function getEarliestStableVersion(): ?string {
    static $cache = [];
    $url = getenv('CIVIX_LATEST_STABLE_URL') ?: 'https://latest.civicrm.org/stable.php?format=json&version=6.4';

    if (!array_key_exists($url, $cache)) {
      $cache[$url] = NULL;
      $context = stream_context_create([
        'http' => [
          'timeout' => 3,
          'user_agent' => 'civix',
        ],
      ]);
      $json = @file_get_contents($url, FALSE, $context) ?: NULL;
      if ($json) {
        $cache[$url] = json_decode($json, TRUE);
      }
      if (is_array($cache[$url])) {
        uksort($cache[$url], 'version_compare');
      }
    }

    foreach ($cache[$url] ?? [] as $ver => $info) {
      if (($info['status'] ?? '') === 'stable') {
        return (string) $ver;
      }
    }
    return NULL;
  }

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
