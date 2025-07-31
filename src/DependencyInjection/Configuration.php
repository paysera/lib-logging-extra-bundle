<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\DependencyInjection;

use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $treeBuilder = new TreeBuilder('paysera_logging_extra');
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('paysera_logging_extra');
        }

        $children = $rootNode->children();
        $children
            ->scalarNode('application_name')
            ->isRequired()
        ;
        $children
            ->arrayNode('grouped_exceptions')
            ->prototype('scalar')
        ;
        $children
            ->arrayNode('monolog')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('minimum_introspection_level')
                        ->isRequired()
                        ->defaultValue(Logger::ERROR)
                    ->end()
                ->end()
            ->end()
        ;
        $children
            ->arrayNode('sentry')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('minimum_log_level')
                        ->isRequired()
                        ->defaultValue(Logger::ERROR)
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
