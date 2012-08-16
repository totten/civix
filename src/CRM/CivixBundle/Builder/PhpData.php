<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Builder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Read/write a serialized data file based on PHP's var_export() format
 */
class PhpData implements Builder {

    /**
     * @var string
     */
    protected $path;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var
     */
    protected $header;

    function __construct($path, $header = NULL) {
        $this->path = $path;
        $this->header = $header;
    }

    /**
     * Get the xml document
     *
     * @return array
     */
    function get() {
        return $this->data;
    }

    function set($data) {
        $this->data = $data;
    }

    function loadInit(&$ctx) {
        if (file_exists($this->path)) {
            $this->load($ctx);
        } else {
            $this->init($ctx);
        }
    }

    /**
     * Initialize a new var_export() document
     */
    function init(&$ctx) {
    }

    /**
     * Read from file
     */
    function load(&$ctx) {
        $this->data = include $this->path;
    }

    /**
     * Write the xml document
     */
    function save(&$ctx, OutputInterface $output) {
        $output->writeln("<info>Write " . $this->path . "</info>");

        $content = "<?php\n";
        if ($this->header) {
            $content .= $this->header;
        }
        $content .= "\nreturn ";
        $content .= var_export($this->data, TRUE);
        $content .= ";";
        file_put_contents($this->path, $content);
    }
}
