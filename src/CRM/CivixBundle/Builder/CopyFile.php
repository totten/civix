<?php
namespace CRM\CivixBundle\Builder;

use SimpleXMLElement;
use DOMDocument;
use CRM\CivixBundle\Builder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build/update a file based on a template
 */
class CopyFile implements Builder {

    protected $from;
    protected $to;
    protected $xml;
    protected $enable;

    /**
     * @param $overwrite scalar; TRUE (always overwrite), FALSE (preserve with error), 'ignore' (preserve quietly)
     */
    function __construct($from, $to, $overwrite) {
        $this->from = $from;
        $this->to = $to;
        $this->overwrite = $overwrite;
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
        if (file_exists($this->to) && $this->overwrite == 'ignore') {
            // do nothing
        } elseif (file_exists($this->to) && !$this->overwrite) {
            $output->writeln("<error>Skip " . $this->to . ": file already exists</error>");
        } else {
            $output->writeln("<info>Write " . $this->to . "</info>");
            $content = file_get_contents($this->from, TRUE);
            file_put_contents($this->to, $content);
        }
    }
}
