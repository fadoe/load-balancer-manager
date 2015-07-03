<?php
namespace Marktjagd\LoadBalancerManager\Command;

use Marktjagd\LoadBalancerManager\LoadBalancer\LoadBalancerFactory;
use Symfony\Component\Console\Command\Command as BaseCommand;

abstract class AbstractCommand extends BaseCommand
{
    private $loadBalancerFactory;
    private $config;

    /**
     * @param array $config
     */
    public function setLoadBalancerConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return LoadBalancerFactory
     */
    public function getLoadBalancerFactory()
    {
        if (null === $this->loadBalancerFactory) {
            $application = $this->getApplication();

            $this->loadBalancerFactory = $application->getLoadBalancerFactory();
        }

        return $this->loadBalancerFactory;
    }
}
