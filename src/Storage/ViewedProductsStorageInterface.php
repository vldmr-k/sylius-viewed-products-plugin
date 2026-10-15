<?php

declare(strict_types=1);

namespace Vldmrk\SyliusViewedProductsPlugin\Storage;

use Sylius\Component\Core\Model\ProductInterface;

/**
 * The ids of the products a visitor viewed, the last viewed first.
 * Implementations: SessionViewedProductsStorage, CookieViewedProductsStorage (config "storage.type").
 */
interface ViewedProductsStorageInterface
{
    public function set(ProductInterface $product): void;

    /**
     * @return int[]
     */
    public function get(): array;

    /**
     * @return int[]
     */
    public function all(): array;
}
