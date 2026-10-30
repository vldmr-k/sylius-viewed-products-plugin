<?php

namespace Vldmrk\SyliusViewedProductsPlugin\Twig;


use Twig\TwigFunction;
use Vldmrk\SyliusViewedProductsPlugin\Service\ViewedProductsServiceInterface;

class ViewedProductsExtension extends \Twig\Extension\AbstractExtension  {

    public function __construct(
        private ViewedProductsServiceInterface $viewedProductsService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('viewed_products_list', [$this->viewedProductsService, 'retrieveProducts']),
        ];
    }


}
