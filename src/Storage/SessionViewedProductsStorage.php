<?php

declare(strict_types=1);

namespace Vldmrk\SyliusViewedProductsPlugin\Storage;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Storage\StorageInterface;

/**
 * Keeps the list in the session, as JSON under "_viewed_product".
 */
final class SessionViewedProductsStorage implements ViewedProductsStorageInterface
{
    private const KEY = '_viewed_product';

    public function __construct(
        private StorageInterface $storage,
        private int $maxItems = 20,
    ) {
    }

    public function set(ProductInterface $product): void
    {
        $items = ViewedProductIds::prepend($this->get(), (int) $product->getId(), $this->maxItems);
        $this->storage->set(self::KEY, json_encode($items));
    }

    public function get(): array
    {
        $data = $this->storage->get(self::KEY, '[]');
        $items = is_string($data) ? json_decode($data, true) : null;

        return is_array($items) ? ViewedProductIds::normalize($items, $this->maxItems) : [];
    }

    public function all(): array
    {
        return $this->get();
    }
}
