<?php
namespace CRM\CivixBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 *
 */
abstract class BaseCommand extends ContainerAwareCommand
{
    /**
     * @var ContainerInterface
     *
    private $container;
    
    function __construct($container) {
        $this->container = $container;
        parent::__construct();
    }

    /**
     * @return ContainerInterface
     *
    protected function getContainer()
    {
        return $this->container;
    }
    */
}
