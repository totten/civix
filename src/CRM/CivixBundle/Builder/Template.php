<?php
namespace CRM\CivixBundle\Builder;

use Civix;

/**
 * Build/update a file based on a template
 */
class Template extends Content {

  protected $template;
  protected $xml;
  protected $templateEngine;

  /**
   * @param string $template
   * @param string $path
   * @param bool|string $overwrite TRUE (always overwrite), FALSE (preserve with error), 'ignore' (preserve quietly)
   * @param \Symfony\Component\Templating\EngineInterface|null $templateEngine
   * @param
   */
  public function __construct(string $template, string $path, $overwrite = FALSE, $templateEngine = NULL) {
    $this->template = $template;
    $this->path = $path;
    $this->overwrite = $overwrite;
    $this->templateEngine = $templateEngine ?: Civix::templating();
  }

  protected function getContent($ctx): string {
    return $this->templateEngine->render($this->template, $ctx);
  }

}
