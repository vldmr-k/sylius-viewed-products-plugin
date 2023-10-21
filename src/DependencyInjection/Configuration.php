<?php

declare(strict_types=1);

namespace Vldmrk\SyliusViewedProductsPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UnusedVariable
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('vldmrk_sylius_viewed_products_plugin');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->arrayNode('storage')
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('limit')
                    ->defaultValue(20)
                ->end();
        return $treeBuilder;
    }
}
