<?php
namespace CRM\Civix\Builder;

use CRM\Civix\Builder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build/update empty directories
 */
class Dirs implements Builder {
    const MODE = 0755;
    
    function __construct($paths) {
        $this->paths = $paths;
    }
    
    function loadInit(&$ctx) {
    }
    
    function init(&$ctx) {
    }
    
    function load(&$ctx) {
    }
    
    function save(&$ctx, OutputInterface $output) {
        sort($this->paths);
        foreach ($this->paths as $dir) {
            $parts = explode(DIRECTORY_SEPARATOR, $dir);
            if (!is_dir($dir)) {
                $output->writeln("<info>Create ${dir}/</info>");
                mkdir($dir, self::MODE, TRUE);
            } else {
                // $output->writeln("<comment>Found ${dir}/</comment>");
            }
        }
    }
}
