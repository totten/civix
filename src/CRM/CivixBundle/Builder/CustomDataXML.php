<?php
namespace CRM\CivixBundle\Builder;

use SimpleXMLElement;
use DOMDocument;
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
     * @param boolean|string $overwrite ; TRUE (always overwrite), FALSE (preserve with error), 'ignore' (preserve quietly)
     */
    function __construct($customGroupIds, $ufGroupIds, $path, $overwrite) {
        $this->customGroupIds = $customGroupIds;
        $this->ufGroupIds = $ufGroupIds;
        $this->path = $path;
        $this->overwrite = $overwrite;
        $this->export = new \CRM_Utils_Migrate_Export();
        $this->export->buildCustomGroups($this->customGroupIds);
        $this->export->buildUFGroups($this->ufGroupIds);
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
            file_put_contents($this->path, $this->export->toXML());
        }
    }
}
