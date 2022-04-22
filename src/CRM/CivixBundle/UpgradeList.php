<?php
namespace CRM\CivixBundle;

class UpgradeList {

  /**
   * @var array|null
   */
  protected $upgrades = NULL;

  /**
   * Get the highest known version.
   *
   * @return string
   */
  public function getHeadVersion(): string {
    return array_key_last($this->getUpgrades());
  }

  /**
   * @return array
   *   array(string $version => string $filePath)
   */
  public function getUpgrades(): array {
    if ($this->upgrades === NULL) {
      $this->upgrades = $this->scan();
    }
    return $this->upgrades;
  }

  /**
   * Get a list of upgrades appropriate to a particular codebase.
   *
   * @param string $startVersion
   *   The initial/start version of the codebase.
   * @return array
   *   array(string $version => string $filePath)
   */
  public function findUpgrades(string $startVersion): array {
    return array_filter($this->getUpgrades(),
      function($upgradeVersion) use ($startVersion) {
        return (bool) version_compare($upgradeVersion, $startVersion, '>');
      },
      ARRAY_FILTER_USE_KEY
    );
  }

  /**
   * Scan for upgrade files.
   *
   * @return array
   *   array(string $version => string $filePath)
   */
  protected function scan(): array {
    $parseVer = function($file) {
      $basename = basename($file);
      return preg_replace(';\.up\.php$;', '', $basename);
    };

    $upgrades = [];
    $iter = new \DirectoryIterator(Application::findCivixDir() . '/upgrades');
    foreach ($iter as $file) {
      /** @var \SplFileInfo $file */
      $upgrades[$parseVer($file->getBasename())] = $file->getPathname();
    }

    uksort($upgrades, 'version_compare');
    return $upgrades;
  }

}
