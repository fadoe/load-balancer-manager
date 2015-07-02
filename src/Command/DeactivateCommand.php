<?php
namespace Marktjagd\LoadBalancerManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeactivateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('deactivate')
            ->setDescription('Deactivate a host on load balancer')
            ->setHelp('The <info>deactivate</info> command activates a webserver over the given load balancer.')
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

        $adapter = $loadBalancerFactory->getLoadBalancerAdapter($loadBalancer);

        $result = $adapter->deactivateWebserver($loadBalancer, $webServer);
        if (true === $result) {
            $output->writeln(sprintf('<info>Worker %s on load balancer %s deactivated</info>', $webServer, $loadBalancer));

            return 0;
        }

        $output->writeln(
            sprintf('<error>Error while deactivating %s on load balancer %s</error>', $webServer, $loadBalancer)
        );

        return 1;
    }
}
