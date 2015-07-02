<?php
namespace Marktjagd\LoadBalancerManager\Command;

use Marktjagd\LoadBalancerManager\LoadBalancer\LoadBalancerFactory;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractCommand extends BaseCommand implements ContainerAwareInterface
{
    private $container;
    private $loadBalancerFactory;

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        if (null === $this->container) {
            $application = $this->getApplication();
            if (null === $application) {
                throw new \LogicException(
                    'The container can not be retrieved as the application instance is not yet set.'
                );
            }

            $this->setContainer($application->getContainer());
        }

        return $this->container;
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

    /**
     * @return array
     */
    protected function getLoadBalancerConfig()
    {
        return $this->getContainer()->getParameter('marktjagd_load_balancer_manager');
    }
}
