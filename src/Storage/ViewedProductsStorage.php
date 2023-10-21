<?php
declare(strict_types=1);

namespace Vldmrk\SyliusViewedProductsPlugin\Storage;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Storage\StorageInterface;

final class ViewedProductsStorage implements ViewedProductsStorageInterface {

    public function __construct(
        private StorageInterface $storage,
        private int $maxItems = 20
    )
    {
    }

    /**
     * @param ProductInterface $product
     */
    public function set(ProductInterface $product): void
    {
        $items = $this->all();
        array_unshift($items, $product->getId());
        $items = array_unique($items);
        $items = $this->normalize($items);
        $this->storage->set($this->provideKey(), $this->encode($items));
    }

    /**
     * @return array
     */
    public function get(): array
    {
        /** @var string $data */
        $data = $this->storage->get($this->provideKey(), '[]');
        return $this->decode($data);
    }

    /**
     * @return array
     */
    public function all() : array {
        return $this->get();
    }

    /**
     * @return string
     */
    private function provideKey(): string
    {
        return '_viewed_product';
    }

    /**
     * @param array $items
     * @return string
     */
    private function encode(array $items): string {
        $items = json_encode($items);
        return (json_last_error() != null) ? '[]' : (string)$items;
    }

    /**
     * @param string $value
     * @return array
     */
    private function decode(string $value): array {
        $items = \json_decode($value, true);
        return (json_last_error() != null) ? [] : (array)$items;
    }

    private function normalize(array $items): array {
        $items = array_map('intval', $items);
        return array_slice($items, 0, $this->maxItems);
    }
}
