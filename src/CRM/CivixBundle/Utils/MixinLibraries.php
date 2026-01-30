<?php

namespace CRM\CivixBundle\Utils;

/**
 * Manage a set of files in 'mixin/libs/'
 */
class MixinLibraries {

  /**
   * @var \CRM\CivixBundle\Utils\Path
   */
  protected $activeDir;

  /**
   * @var \CRM\CivixBundle\Utils\Path
   */
  protected $availableDir;

  /**
   * @var \CRM\CivixBundle\Utils\PathloadPackage[]
   */
  public $active;

  /**
   * @var \CRM\CivixBundle\Utils\PathloadPackage[]
   */
  public $available;

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  public function __construct($activeDir, $availableDir) {
    $this->activeDir = Path::for($activeDir);
    $this->availableDir = Path::for($availableDir);
    $this->fs = new \Symfony\Component\Filesystem\Filesystem();
    $this->refresh();
  }

  /**
   * Add $majorName to the mixin/lib/ folder.
   *
   * If a current/newer version already exists, this is a null-op.
   *
   * @param string $majorName
   */
  public function add(string $majorName): void {

    /** @var \CRM\CivixBundle\Utils\PathloadPackage $avail */
    $avail = $this->available[$majorName] ?? NULL;
    /** @var \CRM\CivixBundle\Utils\PathloadPackage $active */
    $active = $this->active[$majorName] ?? NULL;

    if (!$avail) {
      throw new \RuntimeException("Cannot enable unknown library ($majorName)");
    }

    // Each backport-library has a file-format (eg PHAR, directory, or flat php-file).
    // We prefer to copy-through and preserve that file-format. However, some target-environments
    // have constraints. (Ex: With Backdrop+CiviCRM before 6.1, PHAR files could not be loaded.)
    // So we may sometimes convert the file-format for compatibility.
    $preferType = ($avail->type === 'phar' && !\Civix::checker()->coreEnablesPhar()) ? 'dir' : $avail->type;

    if ($active) {
      if (version_compare($active->version, $avail->version, '>=') && $active->type === $preferType) {
        return;
      }
      else {
        $this->remove($majorName);
      }
    }

    switch ($avail->type . '->' . $preferType) {
      case 'dir->dir':
        $newFile = $this->activeDir->string(basename($avail->file));
        \Civix::output()->writeln("<info>Write</info> " . Files::relativize($newFile));
        $this->activeDir->mkdir();
        $this->fs->mirror($avail->file, $newFile);
        break;

      case 'php->php':
      case 'phar->phar':
        $newFile = $this->activeDir->string(basename($avail->file));
        \Civix::output()->writeln("<info>Write</info> " . Files::relativize($newFile));
        $this->activeDir->mkdir();
        copy($avail->file, $newFile);
        break;

      case 'phar->dir':
        $newFile = $this->activeDir->string(preg_replace('/\.phar$/', '', basename($avail->file)));
        \Civix::output()->writeln("<info>Write</info> " . Files::relativize($newFile));
        $this->activeDir->mkdir();
        $this->extractPhar($avail->file, $newFile);
        break;

      default:
        throw new \RuntimeException("Cannot write library ($majorName) with given type ($avail->type -> $preferType)");
    }

    $this->refresh();
  }

  protected function extractPhar(string $srcPhar, string $destFolder): void {
    $isNestedPhar = preg_match(';^phar://;', $srcPhar);
    if ($isNestedPhar) {
      // Two steps: Copy the nested PHAR to regular file, then extract.
      $tmpFile = dirname($destFolder) . DIRECTORY_SEPARATOR . basename($destFolder) . '.tmp.phar';
      $this->fs->copy($srcPhar, $tmpFile, TRUE);
      $pharOp = new \Phar($tmpFile);
      $pharOp->extractTo($destFolder);
      $this->fs->remove($tmpFile);
    }
    else {
      $pharOp = new \Phar($srcPhar);
      $pharOp->extractTo($destFolder);
    }
  }

  /**
   * Delete $majorName from the mixin/lib/ folder.
   *
   * @param string $majorName
   */
  public function remove(string $majorName): void {
    /** @var \CRM\CivixBundle\Utils\PathloadPackage $active */
    $active = $this->active[$majorName] ?? NULL;
    if ($active) {
      \Civix::output()->writeln("<info>Remove</info> " . Files::relativize($active->file));
      $this->fs->remove($active->file);
    }
    $this->refresh();
  }

  public function toggle(string $majorName, bool $active): void {
    if ($active) {
      $this->add($majorName);
    }
    else {
      $this->remove($majorName);
    }
  }

  public function refresh(): void {
    $this->active = static::scan($this->activeDir);
    $this->available = static::scan($this->availableDir);
  }

  /**
   * @param string $majorName
   *   Either major-name or a wildcard.
   *   Ex: 'civimix-schema@5' or '*'
   * @return bool
   */
  public function hasActive(string $majorName = '*'): bool {
    return $majorName === '*' ? !empty($this->active) : isset($this->active[$majorName]);
  }

  protected static function scan(Path $libDir): array {
    if (!is_dir($libDir->string())) {
      return [];
    }

    $files = Path::for($libDir)->search('glob:*@*');
    $packages = array_map([PathloadPackage::class, 'create'], $files);
    $result = [];
    foreach ($packages as $package) {
      /** @var \CRM\CivixBundle\Utils\PathloadPackage $package */
      $result[$package->majorName] = $package;
    }
    return $result;
  }

}
