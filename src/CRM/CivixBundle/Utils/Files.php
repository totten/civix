<?php

namespace CRM\CivixBundle\Utils;

class Files {

  /**
   * @param $dir
   * @param $pattern
   * @return array
   */
  public static function findFiles($dir, $pattern) {
    if (!is_dir($dir) || !is_readable($dir)) {
      return [];
    }

    $dir = rtrim($dir, '/');
    $todos = [$dir];
    $result = [];
    while (!empty($todos)) {
      $subdir = array_shift($todos);
      $matches = glob("$subdir/$pattern");
      if (is_array($matches)) {
        foreach ($matches as $match) {
          if (!is_dir($match)) {
            $result[] = $match;
          }
        }
      }
      // Find subdirs to recurse into.
      if ($dh = opendir($subdir)) {
        while (FALSE !== ($entry = readdir($dh))) {
          $path = $subdir . DIRECTORY_SEPARATOR . $entry;
          // Exclude . (self) and .. (parent) to avoid infinite loop.
          // Exclude configured exclude dirs.
          // Exclude dirs we can't read.
          // Exclude anything that's not a dir.
          if (
            $entry !== '.'
            && $entry !== '..'
            && (empty($excludeDirsPattern) || !preg_match($excludeDirsPattern, $path))
            && is_dir($path)
            && is_readable($path)
          ) {
            $todos[] = $path;
          }
        }
        closedir($dh);
      }
    }
    return $result;
  }

  /**
   * Make a file path relative to some base dir.
   *
   * @param $directory
   * @param $basePath
   *
   * @return string
   */
  public static function relativize($directory, $basePath) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      $directory = strtr($directory, '\\', '/');
      $basePath = strtr($basePath, '\\', '/');
    }
    if (substr($directory, 0, strlen($basePath)) == $basePath) {
      return substr($directory, strlen($basePath));
    }
    else {
      return $directory;
    }
  }

}
