<?php
namespace Marktjagd\LoadBalancerManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActivateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('activate')
            ->setDescription('Activate a host on load balancer')
            ->setHelp('The <info>activate</info> command activates a webserver over the given load balancer.')
            ->addArgument('loadbalancer', InputArgument::REQUIRED, 'The URL of the load balancer')
            ->addArgument('webserver', InputArgument::REQUIRED, 'The webserver to activate');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loadBalancer = $input->getArgument('loadbalancer');
        $webServer = $input->getArgument('webserver');

        $loadBalancerFactory = $this->getLoadBalancerFactory();

        $adapters = $loadBalancerFactory->getLoadBalancerAdapter($loadBalancer);

        $errors = false;
        foreach ($adapters as $adapter) {
            $result = $adapter->activateWebserver($webServer);
            $loadBalancerUrl = $adapter->getLoadBalancerUrl();

            if (true === $result) {
                $output->writeln(
                    sprintf(
                        '<info>Worker %s on load balancer %s (%s) activated</info>',
                        $webServer,
                        $loadBalancer,
                        $loadBalancerUrl
                    )
                );

                continue;
            }

            $errors = true;
            $output->writeln(
                sprintf(
                    '<error>Error while activating worker %s (%s) on load balancer %s</error>',
                    $webServer,
                    $loadBalancer,
                    $loadBalancerUrl
                )
            );
        }

        return (int) $errors;
    }
}
