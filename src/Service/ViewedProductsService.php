<?php
declare(strict_types=1);

namespace Vldmrk\SyliusViewedProductsPlugin\Service;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Vldmrk\SyliusViewedProductsPlugin\Storage\ViewedProductsStorageInterface;

class ViewedProductsService implements ViewedProductsServiceInterface {

    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ViewedProductsStorageInterface $viewedProductsStorage,
        private LocaleContextInterface $localeContext
    ) {
    }

    /**
     * @param int $limit
     *
     * @return array<int, mixed>|ProductInterface[]
     */
    public function retrieveProducts(int $limit = 10): array {
        $ids = $this->viewedProductsStorage->all();
        if(count($ids) <= 0) {
            return [];
        }

        $locale = $this->localeContext->getLocaleCode();
        $qb = $this->productRepository->createListQueryBuilder($locale);

        /** @var ProductInterface[] $result */
        $result = $qb->andWhere($qb->expr()->in('o.id', $ids))
            ->andWhere($qb->expr()->eq('o.enabled', true))
            ->getQuery()
            ->setMaxResults($limit)
            ->getResult();

        //here we sort array by sortedIds
        $inverterIds = array_flip($ids);
        usort($result, function(ProductInterface $a, ProductInterface $b) use($inverterIds) {
            $aid = $a->getId();
            $bid = $b->getId();
            return $inverterIds[$aid] > $inverterIds[$bid] ? 1 : -1;
        });

        return $result;
    }

}
