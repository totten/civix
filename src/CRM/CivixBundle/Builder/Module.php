<?php
namespace CRM\CivixBundle\Builder;

use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder;
use CRM\CivixBundle\Utils\Path;

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
            'CRMCivixBundle:Code:module.php.php',
            $basedir->string($ctx['mainFile'] . '.php'),
            'ignore',
            $this->templateEngine
        );
        $module->save($ctx, $output);

        $moduleCivix = new Template(
            'CRMCivixBundle:Code:module.civix.php.php',
            $basedir->string($ctx['mainFile'] . '.civix.php'),
            TRUE,
            $this->templateEngine
        );
        $moduleCivix->save($ctx, $output);
    }
}
