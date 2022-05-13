<?php

namespace CRM\CivixBundle\Utils;

class Path {

  public function __construct($basedir) {
    $this->basedir = $basedir;
  }

  public function __toString(): string {
    return $this->basedir;
  }

  /**
   * Determine the full path to a file underneath this path
   *
   * ex: $basepath = $path->string()
   * ex: $item = $this->string('subdir', 'file.xml');
   *
   * @return string
   */
  public function string() {
    $args = func_get_args();
    array_unshift($args, $this->basedir);
    return implode(DIRECTORY_SEPARATOR, $args);
  }

  /**
   * Construct the full path to a file underneath this path
   *
   * ex: $item = $this->path('subdir', 'file.xml');
   *
   * @return Path
   */
  public function path() {
    $args = func_get_args();
    array_unshift($args, $this->basedir);
    return new Path(implode(DIRECTORY_SEPARATOR, $args));
  }

  /**
   * Make a folder for this path (if necessary).
   *
   * @param int $mode
   * @return bool
   *   TRUE if folder exists/is-created. FALSE if an error prevented it.
   */
  public function mkdir($mode = 0777) {
    $args = func_get_args();
    $dir = call_user_func_array([$this, 'string'], $args);
    if (!is_dir($dir)) {
      return mkdir($dir, $mode, TRUE);
    }
    return TRUE;
  }

  /**
   * Recursively search for files matching $pattern.
   *
   * @param string $pattern
   *   Ex: 'glob:foobar/*.xml' (non-recursive search)
   *   Ex: 'find:*.whizbang.php' (recursive search, under .)
   *   Ex: 'find:meta/*.whizbang.php' (recursive search, under ./meta)
   * @return array
   */
  public function search($pattern): array {
    [$patternType, $patternValue] = explode(':', $pattern, 2);
    switch ($patternType) {
      case 'glob':
        return (array) glob($this->string($patternValue));

      case 'find':
        $relDir = dirname($patternValue);
        $filePat = basename($patternValue);
        return Files::findFiles($this->string($relDir), $filePat);

      default:
        throw new \RuntimeException("Unrecognized file pattern: $pattern");
    }
  }

}
