<?php

declare(strict_types=1);

namespace Tests\Vldmrk\SyliusViewedProductsPlugin\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Vldmrk\SyliusViewedProductsPlugin\Service\ViewedProductsServiceInterface;
use Vldmrk\SyliusViewedProductsPlugin\Twig\ViewedProductsExtension;

final class ViewedProductsExtensionTest extends TestCase
{
    public function testTheOnlyFunctionIsTheViewedProductsList(): void
    {
        $functions = (new ViewedProductsExtension($this->createMock(ViewedProductsServiceInterface::class)))->getFunctions();

        self::assertCount(1, $functions);
        self::assertSame('viewed_products_list', $functions[0]->getName());
    }

    public function testTheFunctionPassesTheLimitToTheService(): void
    {
        $service = $this->createMock(ViewedProductsServiceInterface::class);
        $service->expects(self::once())->method('retrieveProducts')->with(4)->willReturn(['a', 'b']);

        self::assertSame('a,b', $this->render($service, '{{ viewed_products_list(4)|join(",") }}'));
    }

    public function testWithoutALimitTheTemplateGetsTheDefaultOfTheService(): void
    {
        // The plugin's own template calls viewed_products_list() with no argument.
        $service = new class() implements ViewedProductsServiceInterface {
            public function retrieveProducts(int $limit = 10): array
            {
                return [$limit];
            }
        };

        self::assertSame('10', $this->render($service, '{{ viewed_products_list()|join(",") }}'));
    }

    private function render(ViewedProductsServiceInterface $service, string $template): string
    {
        $twig = new Environment(new ArrayLoader(['page' => $template]));
        $twig->addExtension(new ViewedProductsExtension($service));

        return $twig->render('page');
    }
}
