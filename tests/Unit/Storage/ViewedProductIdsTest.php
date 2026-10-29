<?php

declare(strict_types=1);

namespace Tests\Vldmrk\SyliusViewedProductsPlugin\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Vldmrk\SyliusViewedProductsPlugin\Storage\ViewedProductIds;

final class ViewedProductIdsTest extends TestCase
{
    public function testTheViewedProductGoesFirst(): void
    {
        self::assertSame([40, 10, 20, 30], ViewedProductIds::prepend([10, 20, 30], 40, 10));
    }

    public function testAProductViewedAgainMovesToTheFrontWithoutARepeat(): void
    {
        self::assertSame([20, 10, 30], ViewedProductIds::prepend([10, 20, 30], 20, 10));
    }

    public function testTheOldestProductFallsOffAtTheLimit(): void
    {
        self::assertSame([40, 10, 20], ViewedProductIds::prepend([10, 20, 30], 40, 3));
    }

    public function testTheFirstViewStartsTheList(): void
    {
        self::assertSame([7], ViewedProductIds::prepend([], 7, 20));
    }

    public function testAProductWithoutAnIdIsNotKept(): void
    {
        // A product without an id comes as 0 from the storages' (int) cast.
        self::assertSame([10], ViewedProductIds::prepend([10], 0, 20));
    }

    /**
     * @dataProvider visitorLists
     */
    public function testOnlyPositiveIntegerIdsAreKept(array $ids, array $expected): void
    {
        self::assertSame($expected, ViewedProductIds::normalize($ids, 20));
    }

    public static function visitorLists(): array
    {
        return [
            'strings from a cookie' => [['20', '10'], [20, 10]],
            'garbage' => [['abc', '-5', '0', '7x', '', ' ', '1.5', null, false, []], []],
            'padded strings' => [[' 5 ', "7\n"], [5, 7]],
            'ints, floats and repeats' => [[3, 3.0, '3', 4, -1], [3, 4]],
            'empty' => [[], []],
        ];
    }

    public function testTrueIsTakenForId1(): void
    {
        // Current behaviour, looks wrong: filter_var() turns true into 1, so a JSON true in the session becomes product 1.
        self::assertSame([1], ViewedProductIds::normalize([true], 20));
    }

    public function testNormalizingStopsAtTheLimit(): void
    {
        self::assertSame([1, 2], ViewedProductIds::normalize([1, 'x', 1, 2, 3, 4], 2));
    }
}
