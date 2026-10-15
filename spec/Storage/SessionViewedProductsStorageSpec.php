<?php

declare(strict_types=1);

namespace spec\Vldmrk\SyliusViewedProductsPlugin\Storage;

use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Storage\StorageInterface;
use Vldmrk\SyliusViewedProductsPlugin\Storage\SessionViewedProductsStorage;
use Vldmrk\SyliusViewedProductsPlugin\Storage\ViewedProductsStorageInterface;

final class SessionViewedProductsStorageSpec extends ObjectBehavior
{
    function let(StorageInterface $storage): void
    {
        $this->beConstructedWith($storage, 3);
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(SessionViewedProductsStorage::class);
        $this->shouldImplement(ViewedProductsStorageInterface::class);
    }

    function it_reads_the_list_from_the_session(StorageInterface $storage): void
    {
        $storage->get('_viewed_product', '[]')->willReturn('[20,10]');

        $this->get()->shouldReturn([20, 10]);
        $this->all()->shouldReturn([20, 10]);
    }

    function it_returns_an_empty_list_for_broken_data(StorageInterface $storage): void
    {
        $storage->get('_viewed_product', '[]')->willReturn('not json');

        $this->get()->shouldReturn([]);
    }

    function it_puts_the_viewed_product_first_without_repeats_and_within_the_limit(
        StorageInterface $storage,
        ProductInterface $product,
    ): void {
        $storage->get('_viewed_product', '[]')->willReturn('[10,20,30]');
        $product->getId()->willReturn(20);

        $storage->set('_viewed_product', '[20,10,30]')->shouldBeCalled();

        $this->set($product);
    }

    function it_drops_the_oldest_product_over_the_limit(StorageInterface $storage, ProductInterface $product): void
    {
        $storage->get('_viewed_product', '[]')->willReturn('[10,20,30]');
        $product->getId()->willReturn(40);

        $storage->set('_viewed_product', '[40,10,20]')->shouldBeCalled();

        $this->set($product);
    }
}
