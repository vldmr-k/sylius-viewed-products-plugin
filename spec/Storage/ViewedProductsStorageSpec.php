<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace spec\Vldmrk\SyliusViewedProductsPlugin\Storage;
use PhpSpec\ObjectBehavior;
use Sylius\Bundle\ResourceBundle\Storage\CookieStorage;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;


final class ViewedProductsStorageSpec extends ObjectBehavior
{
    private $maxItems = 20;

    public function it_add_value($storage, $product) {
        $storage->beADoubleOf(StorageInterface::class);
        $storage->get('_viewed_product', '[]')->willReturn('[]');
        $storage->get('_viewed_product', '[]')->willReturn('[20]');
        $storage->set('_viewed_product', '[20]')->shouldBecalled();

        $product->beADoubleOf(ProductInterface::class);
        $product->getId()->willReturn(20);

        $this->beConstructedWith($storage);
        $this->set($product);
        $this->get()->shouldReturn([20]);
    }
}
