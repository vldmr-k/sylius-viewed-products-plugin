<?php

declare(strict_types=1);

namespace Tests\Vldmrk\SyliusViewedProductsPlugin\Unit;

use PHPUnit\Framework\TestCase;
use Vldmrk\SyliusViewedProductsPlugin\DependencyInjection\VldmrkSyliusViewedProductsExtension;
use Vldmrk\SyliusViewedProductsPlugin\VldmrkSyliusViewedProductsPlugin;

final class VldmrkSyliusViewedProductsPluginTest extends TestCase
{
    public function testTheExtensionAliasFollowsThePluginName(): void
    {
        $extension = (new VldmrkSyliusViewedProductsPlugin())->getContainerExtension();

        self::assertInstanceOf(VldmrkSyliusViewedProductsExtension::class, $extension);
        self::assertSame('vldmrk_sylius_viewed_products', $extension->getAlias());
    }

    public function testThePluginRootIsTheRepositoryRoot(): void
    {
        // Resources/ and migrations/ live next to src/, not inside it.
        self::assertSame(\dirname(__DIR__, 2), (new VldmrkSyliusViewedProductsPlugin())->getPath());
    }
}
