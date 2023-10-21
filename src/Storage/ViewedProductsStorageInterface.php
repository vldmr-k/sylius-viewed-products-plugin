<?php

namespace Vldmrk\SyliusViewedProductsPlugin\Storage;

use Sylius\Component\Core\Model\ProductInterface;

interface ViewedProductsStorageInterface {
    /**
     * @param ProductInterface $product
     * @return mixed
     */
    public function set(ProductInterface $product);

    /**
     * @return array
     */
    public function get(): array;

    /**
     * @return array
     */
    public function all(): array;

}
