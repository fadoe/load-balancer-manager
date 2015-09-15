<?php
namespace Marktjagd\LoadBalancerManager\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('show')
            ->setDescription('Shows all known load balancers and webserver')
            ->setHelp('The <info>show</info> command lists all known load balancers and webserver.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $balancers = $this->getApplication()->getLoadBalancerConfig();

        $table = $this->getHelper('table');
        $table->setHeaders(array('Name', 'Host'));

        foreach ($balancers as $balancer => $value) {

            $output->writeln(sprintf('Info about <info>%s</info> on part <info>%s</info>', $balancer, $value['part']));

            $rows = array();
            foreach ($value['hosts'] as $name => $host) {
                $hosts = array_values($host);
                array_splice($hosts, 0, 0, $name);

                $rows[] = $hosts;
            }

            $table->setRows($rows);
            $table->render($output);
        }

        return 0;
    }
}
