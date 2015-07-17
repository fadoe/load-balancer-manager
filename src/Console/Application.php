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
    private $containerBuilder;
    private $configDir;

    public function __construct()
    {
        parent::__construct(static::NAME, static::VERSION);
        $this->containerBuilder = new ContainerBuilder();

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

        $this->setUpConfig($input);

        return parent::doRun($input, $output);
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
        $this->setUpContainerBuilder();

        return $this->containerBuilder->getParameter('marktjagd_load_balancer_manager');
    }

    /**
     * @return Client
     */
    public function getHttpClient()
    {
        return new Client();
    }

    /**
     * @return string
     */
    public function getConfigDirectory()
    {
        $configDir = $this->configDir;
        if (false !== $configDir && !is_dir($configDir)) {
            throw new \RuntimeException(sprintf('Config directory "%s" don\'t exist.', $configDir));
        }

        return $configDir;
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
     * @return ContainerBuilder
     */
    private function setUpContainerBuilder()
    {
        $configDir = $this->getConfigDirectory();
        $lbmExtension = new MarktjagdLoadBalancerManagerExtension($configDir);
        $this->containerBuilder->registerExtension($lbmExtension);

        $loader = new YamlFileLoader($this->containerBuilder, new FileLocator($configDir));
        $loader->load('lbm-config.yml');

        $this->containerBuilder->compile();
    }

    /**
     * @param InputInterface $input
     */
    private function setUpConfig(InputInterface $input)
    {
        $configDir = $input->getParameterOption(array('--config-path', '-c'), 'config');
        $this->configDir = $configDir;
    }
}
