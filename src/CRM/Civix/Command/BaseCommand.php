<?php
namespace CRM\Civix\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
abstract class BaseCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    private $container;
    
    function __construct($container) {
        $this->container = $container;
        parent::__construct();
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }
}
