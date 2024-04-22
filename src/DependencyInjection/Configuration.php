<?php

declare(strict_types=1);

namespace ManiaxAtWork\ContaoGridBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('maniaxatwork_contao_grid');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('translated_labels')
                    ->defaultFalse()
                ->end()
                ->scalarNode('row_class')
                    ->defaultValue('row')
                ->end()
                ->arrayNode('columns')
                    ->integerPrototype()->end()
                    ->defaultValue(range(1, 12))
                ->end()
                ->booleanNode('columns_no_column')
                    ->defaultTrue()
                ->end()
                ->arrayNode('viewports')
                    ->scalarPrototype()->end()
                    ->defaultValue(['xs', 'sm', 'md', 'lg', 'xl'])
                ->end()
                ->booleanNode('viewports_no_viewport')
                    ->defaultTrue()
                ->end()
                ->arrayNode('column_prefixes')
                    ->scalarPrototype()->end()
                    ->defaultValue(['col', 'row-span'])
                ->end()
                ->arrayNode('options_prefixes')
                    ->scalarPrototype()->end()
                    ->defaultValue(['col-start', 'row-start'])
                ->end()
                ->arrayNode('pulls')
                    ->scalarPrototype()->end()
                    ->defaultValue(['pull-left', 'pull-right'])
                ->end()
                ->arrayNode('positioning')
                    ->scalarPrototype()->end()
                    ->defaultValue(['align', 'justify'])
                ->end()
                ->arrayNode('directions')
                    ->scalarPrototype()->end()
                    ->defaultValue(['start', 'center', 'end', 'stretch'])
                ->end()
                ->arrayNode('options_columns')
                    ->integerPrototype()->end()
                    ->defaultValue(range(1, 12))
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
