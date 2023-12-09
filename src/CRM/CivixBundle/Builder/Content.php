<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Builder;
use CRM\CivixBundle\Utils\Files;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Simply write a string to a file
 */
class Content implements Builder {

  private $content;
  protected $path;
  protected $overwrite;

  /**
   * @param string $content
   * @param string $path
   * @param bool|string $overwrite TRUE (always overwrite), FALSE (preserve with error), 'ignore' (preserve quietly)
   * @param
   */
  public function __construct(string $content, string $path, $overwrite = FALSE) {
    $this->content = $content;
    $this->path = $path;
    $this->overwrite = $overwrite;
  }

  public function loadInit(&$ctx) {
  }

  public function init(&$ctx) {
  }

  public function load(&$ctx) {
  }

  /**
   * Write the content
   */
  public function save(&$ctx, OutputInterface $output) {
    $parent = dirname($this->path);
    if (!is_dir($parent)) {
      mkdir($parent, Dirs::MODE, TRUE);
    }
    if (file_exists($this->path) && $this->overwrite === 'ignore') {
      // do nothing
    }
    elseif (file_exists($this->path) && !$this->overwrite) {
      $output->writeln("<error>Skip " . Files::relativize($this->path) . ": file already exists</error>");
    }
    else {
      $output->writeln("<info>Write</info> " . Files::relativize($this->path));
      file_put_contents($this->path, $this->getContent($ctx));
    }
  }

  protected function getContent($ctx): string {
    return $this->content;
  }

}
