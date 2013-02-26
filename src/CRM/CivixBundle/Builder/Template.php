<?php
namespace CRM\CivixBundle\Builder;

use SimpleXMLElement;
use DOMDocument;
use CRM\CivixBundle\Builder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build/update a file based on a template
 */
class Template implements Builder {

    protected $template, $path, $xml, $templateEngine, $enable;

    /**
     * @param $overwrite scalar; TRUE (always overwrite), FALSE (preserve with error), 'ignore' (preserve quietly)
     */
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
        if (file_exists($this->path) && $this->overwrite === 'ignore') {
            // do nothing
        } elseif (file_exists($this->path) && !$this->overwrite) {
            $output->writeln("<error>Skip " . $this->path . ": file already exists</error>");
        } else {
            $output->writeln("<info>Write " . $this->path . "</info>");
            file_put_contents($this->path, $this->templateEngine->render($this->template, $ctx));
        }
    }
}
