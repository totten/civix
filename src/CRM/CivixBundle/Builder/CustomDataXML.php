<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Builder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build/update a file based on a template
 */
class CustomDataXML implements Builder {

  protected $customGroupIds, $ufGroupIds, $path, $overwrite;

  /**
   * @var \CRM_Utils_Migrate_Export
   */
  protected $export;

  /**
   * @param bool|string $overwrite ; TRUE (always overwrite), FALSE (preserve with error), 'ignore' (preserve quietly)
   */
  public function __construct($customGroupIds, $ufGroupIds, $path, $overwrite) {
    $this->customGroupIds = $customGroupIds;
    $this->ufGroupIds = $ufGroupIds;
    $this->path = $path;
    $this->overwrite = $overwrite;
    $this->export = new \CRM_Utils_Migrate_Export();
    $this->export->buildCustomGroups($this->customGroupIds);
    $this->export->buildUFGroups($this->ufGroupIds);
  }

  public function loadInit(&$ctx) {
  }

  public function init(&$ctx) {
  }

  public function load(&$ctx) {
  }

  /**
   * Write the xml document
   */
  public function save(&$ctx, OutputInterface $output) {
    if (file_exists($this->path) && $this->overwrite === 'ignore') {
      // do nothing
    }
    elseif (file_exists($this->path) && !$this->overwrite) {
      $output->writeln("<error>Skip " . $this->path . ": file already exists</error>");
    }
    else {
      $output->writeln("<info>Write</info> " . $this->path);
      file_put_contents($this->path, $this->export->toXML());
    }
  }

}
