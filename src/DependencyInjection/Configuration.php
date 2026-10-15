<?php

declare(strict_types=1);

namespace Vldmrk\SyliusViewedProductsPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Vldmrk\SyliusViewedProductsPlugin\Enum\StorageType;

final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UnusedVariable
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('vldmrk_sylius_viewed_products');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->arrayNode('storage')
            ->addDefaultsIfNotSet()
            ->children()
                ->enumNode('type')
                    ->info('Where the list is kept: "session" or "cookie" (a cookie does not start a session).')
                    ->values(StorageType::values())
                    ->defaultValue(StorageType::Session->value)
                ->end()
                ->integerNode('limit')
                    ->min(1)
                    ->defaultValue(20)
                ->end()
                ->arrayNode('cookie')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('name')
                            ->cannotBeEmpty()
                            ->defaultValue('viewed_products')
                        ->end()
                        ->integerNode('lifetime')
                            ->info('Seconds; every product view extends it.')
                            ->min(1)
                            ->defaultValue(2592000)
                        ->end();

        return $treeBuilder;
    }
}
