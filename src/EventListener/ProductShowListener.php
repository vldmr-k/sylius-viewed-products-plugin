<?php

declare(strict_types=1);

namespace Vldmrk\SyliusViewedProductsPlugin\EventListener;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductInterface;
use Vldmrk\SyliusViewedProductsPlugin\Storage\ViewedProductsStorageInterface;

class ProductShowListener {

    public function __construct(private ViewedProductsStorageInterface $storage)
    {
    }

    /**
     * @param ResourceControllerEvent $event
     */
    public function track(ResourceControllerEvent $event): void {
        /** @var ProductInterface $product */
        $product = $event->getSubject();
        $this->storage->set($product);
    }

}
