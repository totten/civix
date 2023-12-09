<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Builder;
use CRM\CivixBundle\Utils\Files;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Copy and rename a class.
 *
 * Note: Assumes PEAR-style (one class per file; nameDelimiter=_; no
 * namespace) and extremely distinctive class name (plain string
 * substitution - no PHP parsing)
 */
class CopyClass implements Builder {

  protected $srcClassName;
  protected $tgtClassName;
  protected $tgtFile;
  protected $xml;
  protected $enable;
  protected $filter;

  /**
   * @param $overwrite scalar; TRUE (always overwrite), FALSE (preserve with error), 'ignore' (preserve quietly)
   */
  public function __construct($srcClassName, $tgtClassName, $tgtFile, $overwrite, $filter = FALSE) {
    $this->srcClassName = $srcClassName;
    $this->tgtClassName = $tgtClassName;
    $this->tgtFile = $tgtFile;
    $this->overwrite = $overwrite;
    $this->enable = FALSE;
    $this->filter = $filter;
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
    // NOTE: assume classloaders, etal, are already setup
    $clazz = new \ReflectionClass($this->srcClassName);
    if (file_exists($this->tgtFile) && $this->overwrite == 'ignore') {
      // do nothing
    }
    elseif (file_exists($this->tgtFile) && !$this->overwrite) {
      $output->writeln("<error>Skip " . Files::relativize($this->tgtFile) . ": file already exists</error>");
    }
    else {
      $output->writeln("<info>Write</info> " . Files::relativize($this->tgtFile));
      $content = file_get_contents($clazz->getFileName(), TRUE);
      // FIXME parser
      $content = strtr($content, [
        $this->srcClassName => $this->tgtClassName,
      ]);
      if (is_callable($this->filter)) {
        $content = call_user_func($this->filter, $content);
      }
      file_put_contents($this->tgtFile, $content);
    }
  }

}
