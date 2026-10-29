<?php

declare(strict_types=1);

namespace Tests\Vldmrk\SyliusViewedProductsPlugin\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductInterface;
use Vldmrk\SyliusViewedProductsPlugin\EventListener\ProductShowListener;
use Vldmrk\SyliusViewedProductsPlugin\Storage\ViewedProductsStorageInterface;

final class ProductShowListenerTest extends TestCase
{
    public function testTheShownProductIsStored(): void
    {
        $product = $this->createMock(ProductInterface::class);

        $storage = $this->createMock(ViewedProductsStorageInterface::class);
        $storage->expects(self::once())->method('set')->with($product);

        (new ProductShowListener($storage))->track(new ResourceControllerEvent($product));
    }

    public function testASubjectThatIsNotAProductBreaks(): void
    {
        // It is wired to sylius.product.show only, so it does not check the subject.
        $storage = $this->createMock(ViewedProductsStorageInterface::class);
        $storage->expects(self::never())->method('set');

        $this->expectException(\TypeError::class);

        (new ProductShowListener($storage))->track(new ResourceControllerEvent(new \stdClass()));
    }
}
