<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('paysera_logging_extra');
        $rootNode = $treeBuilder->getRootNode();

        $children = $rootNode->children();
        $children->scalarNode('application_name')->isRequired();
        $children->arrayNode('grouped_exceptions')->prototype('scalar');

        return $treeBuilder;
    }
}
