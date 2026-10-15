<?php

declare(strict_types=1);

namespace Vldmrk\SyliusViewedProductsPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Vldmrk\SyliusViewedProductsPlugin\Enum\StorageType;
use Vldmrk\SyliusViewedProductsPlugin\Storage\ViewedProductsStorageInterface;

final class VldmrkSyliusViewedProductsExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    /** @psalm-suppress UnusedVariable */
    public function load(array $configs, ContainerBuilder $container): void
    {

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../Resources'));

        $loader->load('config/services.xml');

        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $storage = $config['storage'];
        $container->setParameter('vldmrk.viewed_products.property.storage.limit', $storage['limit']);
        $container->setParameter('vldmrk.viewed_products.property.storage.cookie.name', $storage['cookie']['name']);
        $container->setParameter('vldmrk.viewed_products.property.storage.cookie.lifetime', $storage['cookie']['lifetime']);

        // Only the chosen storage stays: the cookie one is a response listener, and an unused one would run for nothing.
        $type = StorageType::from($storage['type']);
        foreach (StorageType::cases() as $case) {
            if ($case !== $type) {
                $container->removeDefinition($case->serviceId());
            }
        }
        $container->setAlias('vldmrk.viewed_product.storage.product_viewed_storage', $type->serviceId());
        $container->setAlias(ViewedProductsStorageInterface::class, 'vldmrk.viewed_product.storage.product_viewed_storage');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'DoctrineMigrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@VldmrkSyliusViewedProductsPlugin/migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }
}
