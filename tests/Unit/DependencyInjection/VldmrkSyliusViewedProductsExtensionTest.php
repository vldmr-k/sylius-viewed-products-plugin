<?php

declare(strict_types=1);

namespace Tests\Vldmrk\SyliusViewedProductsPlugin\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Vldmrk\SyliusViewedProductsPlugin\DependencyInjection\VldmrkSyliusViewedProductsExtension;
use Vldmrk\SyliusViewedProductsPlugin\EventListener\ProductShowListener;
use Vldmrk\SyliusViewedProductsPlugin\Service\ViewedProductsService;
use Vldmrk\SyliusViewedProductsPlugin\Storage\CookieViewedProductsStorage;
use Vldmrk\SyliusViewedProductsPlugin\Storage\SessionViewedProductsStorage;
use Vldmrk\SyliusViewedProductsPlugin\Storage\ViewedProductsStorageInterface;
use Vldmrk\SyliusViewedProductsPlugin\Twig\ViewedProductsExtension;

final class VldmrkSyliusViewedProductsExtensionTest extends TestCase
{
    private const STORAGE = 'vldmrk.viewed_product.storage.product_viewed_storage';

    public function testTheStorageSettingsBecomeParameters(): void
    {
        $container = $this->load([['storage' => ['limit' => 50, 'cookie' => ['name' => 'seen', 'lifetime' => 60]]]]);

        self::assertSame(50, $container->getParameter('vldmrk.viewed_products.property.storage.limit'));
        self::assertSame('seen', $container->getParameter('vldmrk.viewed_products.property.storage.cookie.name'));
        self::assertSame(60, $container->getParameter('vldmrk.viewed_products.property.storage.cookie.lifetime'));
    }

    public function testTheSessionStorageIsTheDefaultAndTheCookieOneIsRemoved(): void
    {
        $container = $this->load([]);

        self::assertTrue($container->hasDefinition('vldmrk.viewed_product.storage.session'));
        self::assertFalse($container->hasDefinition('vldmrk.viewed_product.storage.cookie'));
        self::assertSame('vldmrk.viewed_product.storage.session', (string) $container->getAlias(self::STORAGE));
        self::assertSame(self::STORAGE, (string) $container->getAlias(ViewedProductsStorageInterface::class));

        $session = $container->getDefinition('vldmrk.viewed_product.storage.session');
        self::assertSame(SessionViewedProductsStorage::class, $session->getClass());
        self::assertEquals(new Reference('sylius.storage.session'), $session->getArgument(0));
        self::assertSame('%vldmrk.viewed_products.property.storage.limit%', $session->getArgument(1));
    }

    public function testTheCookieStorageListensToResponsesAndIsResetBetweenRequests(): void
    {
        $container = $this->load([['storage' => ['type' => 'cookie']]]);

        self::assertFalse($container->hasDefinition('vldmrk.viewed_product.storage.session'));
        self::assertSame('vldmrk.viewed_product.storage.cookie', (string) $container->getAlias(self::STORAGE));

        $cookie = $container->getDefinition('vldmrk.viewed_product.storage.cookie');
        self::assertSame(CookieViewedProductsStorage::class, $cookie->getClass());
        self::assertTrue($cookie->hasTag('kernel.event_subscriber'));
        self::assertSame([['method' => 'reset']], $cookie->getTag('kernel.reset'));
        self::assertEquals(new Reference('request_stack'), $cookie->getArgument(0));
        self::assertSame([
            '%vldmrk.viewed_products.property.storage.limit%',
            '%vldmrk.viewed_products.property.storage.cookie.name%',
            '%vldmrk.viewed_products.property.storage.cookie.lifetime%',
        ], array_slice($cookie->getArguments(), 1));
    }

    public function testTheListenerTheServiceAndTheTwigExtensionUseTheChosenStorage(): void
    {
        $container = $this->load([]);

        $listener = $container->getDefinition('vldmrk.viewed_product.event_subscriber.product_show_listener');
        self::assertSame(ProductShowListener::class, $listener->getClass());
        self::assertSame([['event' => 'sylius.product.show', 'method' => 'track']], $listener->getTag('kernel.event_listener'));
        self::assertEquals(new Reference(self::STORAGE), $listener->getArgument(0));

        $service = $container->getDefinition('vldmrk.viewed_product.service.product_viewed_service');
        self::assertSame(ViewedProductsService::class, ltrim($service->getClass(), '\\'));
        self::assertEquals(
            [new Reference('sylius.repository.product'), new Reference(self::STORAGE), new Reference('sylius.context.locale')],
            $service->getArguments(),
        );

        $twig = $container->getDefinition('vldmrk.viewed_product.twig.viewed_products_extension');
        self::assertSame(ViewedProductsExtension::class, ltrim($twig->getClass(), '\\'));
        self::assertTrue($twig->hasTag('twig.extension'));
        self::assertEquals(new Reference('vldmrk.viewed_product.service.product_viewed_service'), $twig->getArgument(0));
    }

    public function testPrependDoesNothingWithoutDoctrineMigrations(): void
    {
        $container = new ContainerBuilder();

        (new VldmrkSyliusViewedProductsExtension())->prepend($container);

        self::assertSame([], $container->getExtensionConfig('doctrine_migrations'));
    }

    public function testPrependAddsThePluginMigrationsAfterTheSyliusOnes(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension($this->extensionNamed('doctrine_migrations'));
        $container->registerExtension($this->extensionNamed('sylius_labs_doctrine_migrations_extra'));

        (new VldmrkSyliusViewedProductsExtension())->prepend($container);

        self::assertSame(
            [['migrations_paths' => ['DoctrineMigrations' => '@VldmrkSyliusViewedProductsPlugin/migrations']]],
            $container->getExtensionConfig('doctrine_migrations'),
        );
        self::assertSame(
            [['migrations' => ['DoctrineMigrations' => ['Sylius\Bundle\CoreBundle\Migrations']]]],
            $container->getExtensionConfig('sylius_labs_doctrine_migrations_extra'),
        );
    }

    private function load(array $configs): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new VldmrkSyliusViewedProductsExtension())->load($configs, $container);

        return $container;
    }

    private function extensionNamed(string $alias): Extension
    {
        return new class($alias) extends Extension {
            public function __construct(private string $alias)
            {
            }

            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return $this->alias;
            }
        };
    }
}
