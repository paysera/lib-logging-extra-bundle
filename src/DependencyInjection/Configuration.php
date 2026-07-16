<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $treeBuilder = new TreeBuilder('paysera_logging_extra');
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('paysera_logging_extra');
        }

        $children = $rootNode->children();
        $children->scalarNode('application_name')->isRequired();
        $children->arrayNode('grouped_exceptions')->prototype('scalar');
        $children->scalarNode('trace_id_provider')
            ->defaultNull()
            ->validate()
                ->ifTrue(static function ($value): bool {
                    return $value !== null && trim((string) $value) === '';
                })
                ->thenInvalid('The trace_id_provider must be a non-empty service id, got %s.')
            ->end()
        ;

        return $treeBuilder;
    }
}
