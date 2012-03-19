<?php
namespace CRM\Civix\Builder;

use SimpleXMLElement;
use DOMDocument;
use CRM\Civix\Builder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build/update a file based on a template
 */
class Template implements Builder {

    protected $template, $path, $xml, $templateEngine, $enable;

    function __construct($template, $path, $overwrite, $templateEngine) {
        $this->template = $template;
        $this->path = $path;
        $this->overwrite = $overwrite;
        $this->templateEngine = $templateEngine;
        $this->enable = FALSE;
    }
    
    function loadInit(&$ctx) {
    }
    
    function init(&$ctx) {
    }
    
    function load(&$ctx) {
    }
    
    /**
     * Write the xml document
     */
    function save(&$ctx, OutputInterface $output) {
        if ($this->overwrite || !file_exists($this->path)) {
            $output->writeln("<info>Write " . $this->path . "</info>");
            file_put_contents($this->path, $this->templateEngine->render($this->template, $ctx));
        } else {
            $output->writeln("<error>Skip " . $this->path . ": file already exists</error>");
        }
    }
}
