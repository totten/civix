<?php
namespace CRM\Civix\Builder;

use Symfony\Component\Console\Output\OutputInterface;
use CRM\Civix\Builder;
use CRM\Civix\Utils\Path;

class Module implements Builder {
    function __construct($templateEngine) {
        $this->templateEngine = $templateEngine;
    }
    
    function loadInit(&$ctx) {
    }
    
    function init(&$ctx) {
    }
    
    function load(&$ctx) {
    }
    
    function save(&$ctx, OutputInterface $output) {
        $basedir = new Path($ctx['basedir']);
        $module = new Template(
            'module.php',
            $basedir->string($ctx['mainFile'] . '.php'),
            'ignore',
            $this->templateEngine
        );
        $module->save($ctx, $output);
        
        $moduleCivix = new Template(
            'module.civix.php',
            $basedir->string($ctx['mainFile'] . '.civix.php'),
            TRUE,
            $this->templateEngine
        );
        $moduleCivix->save($ctx, $output);
    }
}
