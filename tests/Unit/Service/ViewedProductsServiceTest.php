<?php

declare(strict_types=1);

namespace Tests\Vldmrk\SyliusViewedProductsPlugin\Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
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
    /** @var QueryBuilder[] The builders whose query was run. */
    private array $queried = [];

    /** @var ProductInterface[] */
    private array $result = [];

    public function testNoViewedProductsGiveAnEmptyListWithoutAQuery(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects(self::never())->method('createListQueryBuilder');

        $locale = $this->createMock(LocaleContextInterface::class);
        $locale->expects(self::never())->method('getLocaleCode');

        self::assertSame([], $this->service($repository, $this->storage([]), $locale)->retrieveProducts());
    }

    public function testEnabledViewedProductsAreAskedInTheCurrentLocale(): void
    {
        $repository = $this->repository('uk_UA', []);

        $this->service($repository, $this->storage([30, 10, 20]), $this->locale('uk_UA'))->retrieveProducts(5);

        self::assertCount(1, $this->queried);
        self::assertSame('SELECT o FROM Product o WHERE o.id IN(30, 10, 20) AND o.enabled = 1', $this->queried[0]->getDQL());
        self::assertNull($this->queried[0]->getMaxResults());
    }

    public function testTheDefaultLimitIsTen(): void
    {
        $products = array_map(fn (int $id): ProductInterface => $this->product($id), range(1, 12));

        $service = $this->service($this->repository('uk_UA', $products), $this->storage(range(1, 12)), $this->locale('uk_UA'));

        self::assertSame(array_slice($products, 0, 10), $service->retrieveProducts());
    }

    public function testProductsComeInTheOrderTheyWereViewed(): void
    {
        $ten = $this->product(10);
        $twenty = $this->product(20);
        $thirty = $this->product(30);

        $service = $this->service($this->repository('en_US', [$ten, $twenty, $thirty]), $this->storage([30, 10, 20]), $this->locale('en_US'));

        self::assertSame([$thirty, $ten, $twenty], $service->retrieveProducts());
        self::assertInstanceOf(ViewedProductsServiceInterface::class, $service);
    }

    public function testTheLastViewedProductsAreShownWhenThereAreMoreThanTheLimit(): void
    {
        // The database returns them in its own order; all stored ids are asked, without a limit in the query.
        $products = [];
        foreach ([5, 50, 1, 3, 49, 2, 4] as $id) {
            $products[$id] = $this->product($id);
        }

        $service = $this->service($this->repository('uk_UA', array_values($products)), $this->storage(range(1, 50)), $this->locale('uk_UA'));

        self::assertSame([$products[1], $products[2], $products[3]], $service->retrieveProducts(3));
        self::assertStringContainsString('WHERE o.id IN(' . implode(', ', range(1, 50)) . ') AND', $this->queried[0]->getDQL());
        self::assertNull($this->queried[0]->getMaxResults());
    }

    public function testDisabledOrMissingProductsDoNotTakePlaces(): void
    {
        // 30 and 10 were viewed last but are disabled or deleted, so the query does not return them.
        $twenty = $this->product(20);
        $forty = $this->product(40);
        $sixty = $this->product(60);

        $service = $this->service($this->repository('uk_UA', [$sixty, $forty, $twenty]), $this->storage([30, 10, 20, 40, 60]), $this->locale('uk_UA'));

        self::assertSame([$twenty, $forty], $service->retrieveProducts(2));
    }

    public function testAZeroLimitGivesNothing(): void
    {
        $service = $this->service($this->repository('uk_UA', [$this->product(1)]), $this->storage([1]), $this->locale('uk_UA'));

        self::assertSame([], $service->retrieveProducts(0));
    }

    private function repository(string $locale, array $result): ProductRepositoryInterface
    {
        // Doctrine\ORM\Query is final in ORM 3: the builder is a real one, and the service's getResult() is stubbed.
        $this->result = $result;

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getExpressionBuilder')->willReturn(new Expr());

        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects(self::once())->method('createListQueryBuilder')->with($locale)
            ->willReturn((new QueryBuilder($manager))->select('o')->from('Product', 'o'));

        return $repository;
    }

    private function service(ProductRepositoryInterface $repository, ViewedProductsStorageInterface $storage, LocaleContextInterface $locale): ViewedProductsService
    {
        $service = $this->getMockBuilder(ViewedProductsService::class)
            ->setConstructorArgs([$repository, $storage, $locale])
            ->onlyMethods(['getResult'])
            ->getMock();
        $service->method('getResult')->willReturnCallback(function (QueryBuilder $builder): array {
            $this->queried[] = $builder;

            return $this->result;
        });

        return $service;
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
