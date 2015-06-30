<?php
namespace Marktjagd\LoadBalancerManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('status')
            ->setDescription('Shows current load balancer status.')
            ->setHelp(
                'The <info>status</info> command outputs a table with the current status of the hosts on load balancer.'
            )
            ->addArgument('loadbalancer', InputArgument::REQUIRED, 'The load balancer')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loadBalancer = $input->getArgument('loadbalancer');

        $factory = $this->getLoadBalancerFactory();
        $adapter = $factory->getLoadBalancerAdapter($loadBalancer);
        $worker = $adapter->getWebserverStatus($loadBalancer);

        $table = $this->getHelper('table');
        $table->setHeaders(array('Worker', 'Status'));
        $table->setRows($worker);

        $output->writeln(
            sprintf(
                'Status for load balancer <info>%s</info> on part <info>%s</info>',
                $loadBalancer,
                $adapter->getLoadBalancerStatusPart()
            )
        );
        $table->render($output);

        return 0;
    }
}
