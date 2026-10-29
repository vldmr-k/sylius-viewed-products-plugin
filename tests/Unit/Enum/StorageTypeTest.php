<?php

declare(strict_types=1);

namespace Tests\Vldmrk\SyliusViewedProductsPlugin\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Vldmrk\SyliusViewedProductsPlugin\Enum\StorageType;

final class StorageTypeTest extends TestCase
{
    public function testTheValuesAreTheConfigChoices(): void
    {
        self::assertSame(['session', 'cookie'], StorageType::values());
    }

    public function testEachTypeNamesItsStorageService(): void
    {
        self::assertSame('vldmrk.viewed_product.storage.session', StorageType::Session->serviceId());
        self::assertSame('vldmrk.viewed_product.storage.cookie', StorageType::Cookie->serviceId());
    }
}
