<?php
namespace Marktjagd\LoadBalancerManager\Console;

use Guzzle\Http\Client;
use Marktjagd\LoadBalancerManager\Command\ActivateCommand;
use Marktjagd\LoadBalancerManager\Command\DeactivateCommand;
use Marktjagd\LoadBalancerManager\Command\ShowCommand;
use Marktjagd\LoadBalancerManager\Command\StatusCommand;
use Marktjagd\LoadBalancerManager\DependencyInjection\MarktjagdLoadBalancerManagerExtension;
use Marktjagd\LoadBalancerManager\LoadBalancer\LoadBalancerFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Application extends BaseApplication
{
    const NAME = 'Load Balancer Manager';
    const VERSION = '1.0-dev';

    /**
     * @var bool
     */
    private $commandsRegistered = false;

    /**
     * @var ContainerBuilder
     */
    private $container;

    public function __construct()
    {
        parent::__construct(static::NAME, static::VERSION);

        $this->getDefinition()->addOption(
            new InputOption(
                '--config-path',
                '-c',
                InputOption::VALUE_OPTIONAL,
                'The path to lbm-config.yml'
            )
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if (false ===$this->commandsRegistered) {
            $this->registerCommands();

            $this->commandsRegistered = true;
        }

        $this->container = $this->setUpContainerBuilder($input);

        $exitCode = parent::doRun($input, $output);

        return $exitCode || 0;
    }

    /**
     * @return LoadBalancerFactory
     */
    public function getLoadBalancerFactory()
    {
        $loadBalancerFactory = new LoadBalancerFactory(
            $this->getHttpClient(),
            $this->getLoadBalancerConfig()
        );

        return $loadBalancerFactory;
    }

    /**
     * @return array
     */
    public function getLoadBalancerConfig()
    {
        return $this->container->getParameter('marktjagd_load_balancer_manager');
    }

    /**
     * @return Client
     */
    public function getHttpClient()
    {
        return new Client();
    }

    private function registerCommands()
    {
        $commands = array(
            new ActivateCommand(),
            new DeactivateCommand(),
            new ShowCommand(),
            new StatusCommand(),
        );

        $this->addCommands($commands);
    }

    /**
     * @param InputInterface $input
     *
     * @return ContainerBuilder
     */
    private function setUpContainerBuilder(InputInterface $input)
    {
        $configDir = $this->getConfigDirectory($input);
        $diExtension = new MarktjagdLoadBalancerManagerExtension($configDir);
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->registerExtension($diExtension);

        $loader = new YamlFileLoader($containerBuilder, new FileLocator($configDir));
        $loader->load('lbm-config.yml');

        $containerBuilder->compile();

        return $containerBuilder;
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    private function getConfigDirectory(InputInterface $input)
    {
        $configDir = $input->getParameterOption(array('--config-path', '-c'), 'config');
        if (false !== $configDir && !is_dir($configDir)) {
            throw new \RuntimeException(sprintf('Config directory "%s" don\'t exist.', $configDir));
        }

        return $configDir;
    }
}
