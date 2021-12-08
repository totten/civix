<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Builder;
use CRM\CivixBundle\Services;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build/update a file based on a template
 */
class Template implements Builder {

  protected $template;
  protected $path;
  protected $xml;
  protected $templateEngine;
  protected $enable;

  /**
   * @param string $template
   * @param string $path
   * @param bool|string $overwrite TRUE (always overwrite), FALSE (preserve with error), 'ignore' (preserve quietly)
   * @param \Symfony\Component\Templating\EngineInterface|null $templateEngine
   * @param
   */
  public function __construct(string $template, string $path, $overwrite, $templateEngine = NULL) {
    $this->template = $template;
    $this->path = $path;
    $this->overwrite = $overwrite;
    $this->templateEngine = $templateEngine ?: Services::templating();
    $this->enable = FALSE;
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
    $parent = dirname($this->path);
    if (!is_dir($parent)) {
      mkdir($parent, Dirs::MODE, TRUE);
    }
    if (file_exists($this->path) && $this->overwrite === 'ignore') {
      // do nothing
    }
    elseif (file_exists($this->path) && !$this->overwrite) {
      $output->writeln("<error>Skip " . $this->path . ": file already exists</error>");
    }
    else {
      $output->writeln("<info>Write</info> " . $this->path);
      file_put_contents($this->path, $this->templateEngine->render($this->template, $ctx));
    }
  }

}
