<?php

namespace CRM\CivixBundle\Utils;

class Files {

  public static function isIdenticalFile(string $a, string $b): bool {
    if (!file_exists($a) || !file_exists($b) || filesize($a) !== filesize($b)) {
      return FALSE;
    }

    $handleA = fopen($a, 'rb');
    $handleB = fopen($b, 'rb');
    if (!$handleA || !$handleB) {
      return FALSE;
    }

    $result = TRUE;
    while (!feof($handleA) && !feof($handleB)) {
      $chunkA = fread($handleA, 4096); // Read a chunk from file A
      $chunkB = fread($handleB, 4096); // Read a chunk from file B

      if ($chunkA !== $chunkB) {
        $result = FALSE; // Files are not identical
        break;
      }
    }

    // Check if both files reached the end simultaneously
    if (!feof($handleA) || !feof($handleB)) {
      $result = FALSE; // Files have different lengths
    }

    fclose($handleA);
    fclose($handleB);

    return $result;
  }

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
   * Determine if any files match the regex.
   *
   * @param string $regex
   * @param array $files
   * @return array
   */
  public static function grepFiles(string $regex, array $files): array {
    $result = [];
    foreach ($files as $file) {
      if (!file_exists($file)) {
        continue;
      }
      $c = file_get_contents($file);
      if (preg_match($regex, $c)) {
        $result[] = $file;
      }
    }
    return $result;
  }

  /**
   * Make a file path relative to some base dir.
   *
   * @param $directory
   * @param string|null $basePath
   *
   * @return string
   */
  public static function relativize($directory, $basePath = NULL) {
    if ($basePath === NULL) {
      $basePath = getcwd();
    }
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      $directory = strtr($directory, '\\', '/');
      $basePath = strtr($basePath, '\\', '/');
    }
    $basePath = rtrim($basePath, '/') . '/';

    if (substr($directory, 0, strlen($basePath)) == $basePath) {
      return substr($directory, strlen($basePath));
    }
    else {
      return $directory;
    }
  }

}
