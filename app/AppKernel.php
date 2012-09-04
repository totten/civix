<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
            new CRM\CivixBundle\CRMCivixBundle(),
            new CRM\ClientBundle\CRMClientBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        if (file_exists(__DIR__.'/config/parameters.yml')) {
            $loader->load(__DIR__.'/config/parameters.yml');
        } else {
            $loader->load(__DIR__.'/config/parameters.dist.yml');
        }
        if (file_exists($this->getHomeDataDir() . '/civix.ini')) {
            $loader->load($this->getHomeDataDir() . '/civix.ini');
        }
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }

    /* Uncomment to move generated data from "app/*" to "~/.civix"; can help with PHAR packaging
    public function getLogDir()
    {
        return $this->getHomeDataDir() . '/logs';
    }

    public function getCacheDir()
    {
        return $this->getHomeDataDir() . '/cache';
    }
    */

    public function getHomeDataDir() {
        return getenv('HOME') . '/.civix';
    }

}
