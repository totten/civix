<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Builder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build/update empty directories
 */
class Dirs implements Builder {
  // Mask applied to new new directory permissions.
  // Note: Permissions will be further restricted by umask
  const MODE = 0777;

  /**
   * @var string[]
   */
  private $paths;

  public function __construct($paths = []) {
    $this->paths = $paths;
  }

  public function addPath(string $path) {
    $this->paths[] = $path;
  }

  public function loadInit(&$ctx) {
  }

  public function init(&$ctx) {
  }

  public function load(&$ctx) {
  }

  public function save(&$ctx, OutputInterface $output) {
    sort($this->paths);
    foreach ($this->paths as $dir) {
      $parts = explode(DIRECTORY_SEPARATOR, $dir);
      if (!is_dir($dir)) {
        //quiet//$output->writeln("<info>Create ${dir}/</info>");
        mkdir($dir, self::MODE, TRUE);
      }
      else {
        // $output->writeln("<comment>Found ${dir}/</comment>");
      }
    }
  }

}
