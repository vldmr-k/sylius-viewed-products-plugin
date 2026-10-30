<?php

declare(strict_types=1);

namespace Tests\Vldmrk\SyliusViewedProductsPlugin\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Resource\Storage\StorageInterface;
use Vldmrk\SyliusViewedProductsPlugin\Storage\SessionViewedProductsStorage;

final class SessionViewedProductsStorageTest extends TestCase
{
    public function testTheListIsReadFromJsonUnderItsKey(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('get')->with('_viewed_product', '[]')->willReturn('[30,"20",10]');

        $viewed = new SessionViewedProductsStorage($storage, 20);

        self::assertSame([30, 20, 10], $viewed->get());
        self::assertSame([30, 20, 10], $viewed->all());
    }

    /**
     * @dataProvider brokenValues
     */
    public function testABrokenValueGivesAnEmptyList($value): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('get')->willReturn($value);

        self::assertSame([], (new SessionViewedProductsStorage($storage))->get());
    }

    public static function brokenValues(): array
    {
        return [
            'not json' => ['{oops'],
            'json scalar' => ['5'],
            'not a string' => [[1, 2]],
            'null' => [null],
        ];
    }

    public function testTheReadListIsCutToTheLimit(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('get')->willReturn('[1,2,3,4]');

        self::assertSame([1, 2], (new SessionViewedProductsStorage($storage, 2))->get());
    }

    public function testTheViewedProductIsWrittenFirstAsJson(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('get')->willReturn('[10,20,30]');
        $storage->expects(self::once())->method('set')->with('_viewed_product', '[20,10,30]');

        (new SessionViewedProductsStorage($storage, 20))->set($this->product(20));
    }

    public function testTheWrittenListKeepsTheLimit(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('get')->willReturn('[10,20,30]');
        $storage->expects(self::once())->method('set')->with('_viewed_product', '[40,10]');

        (new SessionViewedProductsStorage($storage, 2))->set($this->product(40));
    }

    public function testTheDefaultLimitIsTwenty(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('get')->willReturn(json_encode(range(1, 30)));

        self::assertSame(range(1, 20), (new SessionViewedProductsStorage($storage))->get());
    }

    private function product(int $id): ProductInterface
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn($id);

        return $product;
    }
}
