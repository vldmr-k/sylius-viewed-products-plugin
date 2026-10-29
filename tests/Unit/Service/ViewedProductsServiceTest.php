<?php

declare(strict_types=1);

namespace Tests\Vldmrk\SyliusViewedProductsPlugin\Unit\Service;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Vldmrk\SyliusViewedProductsPlugin\Service\ViewedProductsService;
use Vldmrk\SyliusViewedProductsPlugin\Service\ViewedProductsServiceInterface;
use Vldmrk\SyliusViewedProductsPlugin\Storage\ViewedProductsStorageInterface;

final class ViewedProductsServiceTest extends TestCase
{
    /** @var string[] */
    private array $conditions = [];

    private ?int $maxResults = null;

    public function testNoViewedProductsGiveAnEmptyListWithoutAQuery(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects(self::never())->method('createListQueryBuilder');

        $locale = $this->createMock(LocaleContextInterface::class);
        $locale->expects(self::never())->method('getLocaleCode');

        self::assertSame([], (new ViewedProductsService($repository, $this->storage([]), $locale))->retrieveProducts());
    }

    public function testEnabledViewedProductsAreAskedInTheCurrentLocale(): void
    {
        $repository = $this->repository('uk_UA', []);

        (new ViewedProductsService($repository, $this->storage([30, 10, 20]), $this->locale('uk_UA')))->retrieveProducts(5);

        self::assertSame(['o.id IN(30, 10, 20)', 'o.enabled = 1'], $this->conditions);
        self::assertSame(5, $this->maxResults);
    }

    public function testTheDefaultLimitIsTen(): void
    {
        (new ViewedProductsService($this->repository('uk_UA', []), $this->storage([1]), $this->locale('uk_UA')))->retrieveProducts();

        self::assertSame(10, $this->maxResults);
    }

    public function testProductsComeInTheOrderTheyWereViewed(): void
    {
        $ten = $this->product(10);
        $twenty = $this->product(20);
        $thirty = $this->product(30);

        $service = new ViewedProductsService($this->repository('en_US', [$ten, $twenty, $thirty]), $this->storage([30, 10, 20]), $this->locale('en_US'));

        self::assertSame([$thirty, $ten, $twenty], $service->retrieveProducts());
        self::assertInstanceOf(ViewedProductsServiceInterface::class, $service);
    }

    public function testTheLimitIsAppliedInTheQueryBeforeTheViewOrder(): void
    {
        // Current behaviour, looks wrong: the query has no ORDER BY, so with more viewed products than the
        // limit the database picks which ones are shown, not the last viewed.
        (new ViewedProductsService($this->repository('uk_UA', []), $this->storage(range(1, 50)), $this->locale('uk_UA')))->retrieveProducts(10);

        self::assertSame(10, $this->maxResults);
        self::assertStringContainsString('50)', $this->conditions[0]);
    }

    private function repository(string $locale, array $result): ProductRepositoryInterface
    {
        $query = $this->createMock(Query::class);
        $query->method('setMaxResults')->willReturnCallback(function (?int $max) use ($query): Query {
            $this->maxResults = $max;

            return $query;
        });
        $query->method('getResult')->willReturn($result);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn(new Expr());
        $queryBuilder->method('andWhere')->willReturnCallback(function ($condition) use ($queryBuilder): QueryBuilder {
            $this->conditions[] = (string) $condition;

            return $queryBuilder;
        });
        $queryBuilder->method('getQuery')->willReturn($query);

        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects(self::once())->method('createListQueryBuilder')->with($locale)->willReturn($queryBuilder);

        return $repository;
    }

    private function storage(array $ids): ViewedProductsStorageInterface
    {
        $storage = $this->createMock(ViewedProductsStorageInterface::class);
        $storage->method('all')->willReturn($ids);

        return $storage;
    }

    private function locale(string $code): LocaleContextInterface
    {
        $locale = $this->createMock(LocaleContextInterface::class);
        $locale->method('getLocaleCode')->willReturn($code);

        return $locale;
    }

    private function product(int $id): ProductInterface
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn($id);

        return $product;
    }
}
