<?php
namespace CRM\CivixBundle;

/**
 * Helper methods called via `composer compile`.
 *
 * Note that the application is still being setup, so it may be ill-advised to use higher-level services.
 */
class ComposerCompile {

  /**
   * Download the mixin files
   *
   * Note: We want to read the list from `mixin-backports.php` because we can track extra metadata.
   *
   * @param array $task
   */
  public static function downloadMixins(array $task): void {
    $civix = dirname(__DIR__, 3);
    $mixinListFile = "$civix/mixin-backports.php";
    $mixinListTimestamp = filemtime($mixinListFile);
    $mixins = require $mixinListFile;
    foreach ($mixins as $id => $mixin) {
      $checksum = file_exists($mixin['local']) ? hash('sha256', file_get_contents($mixin['local'])) : '';

      if (!file_exists($mixin['local']) || $checksum !== $mixin['sha256']) {
        printf(" - Download %s\n", $mixin['local']);
        $content = file_get_contents($mixin['remote']);
        $contentChecksum = hash('sha256', $content);
        if ($contentChecksum !== $mixin['sha256']) {
          throw new \RuntimeException("Download from {$mixin['remote']} has wrong checksum. (expect={$mixin['sha256']}, actual=$contentChecksum)");
        }

        static::putFile($mixin['local'], $content);
      }
    }
  }

  protected static function putFile(string $path, string $content): void {
    $outDir = dirname($path);
    if (!is_dir($outDir)) {
      if (!mkdir($outDir, 0777, TRUE)) {
        throw new \RuntimeException("Failed to make dir: $outDir");
      }
    }
    if (FALSE === file_put_contents($path, $content)) {
      throw new \RuntimeException("Failed to write file: $path");
    }
  }

}
