<?php
namespace CRM\Civix;

use Symfony\Component\Console\Output\OutputInterface;

interface Builder {
    function loadInit(&$ctx);
    function init(&$ctx);
    function load(&$ctx);
    function save(&$ctx, OutputInterface $output);
}
