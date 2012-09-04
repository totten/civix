<?php
namespace CRM\CivixBundle\Builder;

use CRM\CivixBundle\Builder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Read/write a serialized data file based on PHP's var_export() format
 */
class Ini implements Builder {

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
        $this->data = parse_ini_file($this->path, TRUE);
    }

    /**
     * Write the xml document
     */
    function save(&$ctx, OutputInterface $output) {
        $output->writeln("<info>Write " . $this->path . "</info>");

        $content = '';
        foreach ($this->data as $topKey => $data) {
            $content .= "[$topKey]\n";
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                  $content .= $this->flatten($key, $value);
                } else {
                  $content .= "$key=$value\n";
                }
            }
        }
        file_put_contents($this->path, $content);
    }

    protected function flatten($prefix, $values) {
        $content = '';
        foreach ($values as $key => $value) {
            if (is_array($values)) {
                $content .= $this->flatten("$prefix[$key]", $value);
            } else {
                $content .= "$prefix[$key]=$value\n";
            }
        }
        return $content;
    }
}
