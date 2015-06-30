<?php
namespace Marktjagd\LoadBalancerManager\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('marktjagd_load_balancer_manager');

        $this->addLoadBalancerSection($rootNode);

        return $treeBuilder;
    }

    private function addLoadBalancerSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->useAttributeAsKey('loadbalancer')
            ->isRequired()
            ->requiresAtLeastOneElement()
                ->prototype('array')
                    ->children()
                        ->scalarNode('host')->isRequired()->canNotBeEmpty()->end()
                        ->scalarNode('part')->isRequired()->canNotBeEmpty()->end()
                        ->arrayNode('auth')
                            ->isRequired()
                            ->children()
                                ->scalarNode('username')->isRequired()->canNotBeEmpty()->end()
                                ->scalarNode('password')->isRequired()->canNotBeEmpty()->end()
                            ->end()
                        ->end()
                        ->arrayNode('hosts')
                            ->isRequired()
                            ->requiresAtLeastOneElement()
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('host')->isRequired()->canNotBeEmpty()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
        ->end();
    }
}
