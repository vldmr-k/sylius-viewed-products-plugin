<?php


namespace Vldmrk\SyliusViewedProductsPlugin\Service;


interface ViewedProductsServiceInterface
{
    public function retrieveProducts(int $limit): array;
}
