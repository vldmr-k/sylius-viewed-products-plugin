<?php

declare(strict_types=1);

namespace spec\Vldmrk\SyliusViewedProductsPlugin\Storage;

use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Vldmrk\SyliusViewedProductsPlugin\Storage\CookieViewedProductsStorage;
use Vldmrk\SyliusViewedProductsPlugin\Storage\ViewedProductsStorageInterface;

final class CookieViewedProductsStorageSpec extends ObjectBehavior
{
    private RequestStack $requestStack;

    function let(): void
    {
        $this->requestStack = new RequestStack();
        $this->beConstructedWith($this->requestStack, 3, 'viewed_products', 3600);
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(CookieViewedProductsStorage::class);
        $this->shouldImplement(ViewedProductsStorageInterface::class);
    }

    function it_returns_an_empty_list_without_a_request_or_a_cookie(): void
    {
        $this->get()->shouldReturn([]);

        $this->requestStack->push(new Request());
        $this->get()->shouldReturn([]);
    }

    function it_reads_the_list_from_the_cookie(): void
    {
        $this->requestStack->push(new Request([], [], [], ['viewed_products' => '20.10']));

        $this->get()->shouldReturn([20, 10]);
        $this->all()->shouldReturn([20, 10]);
    }

    function it_keeps_only_valid_ids_from_the_cookie(): void
    {
        $this->requestStack->push(new Request([], [], [], ['viewed_products' => '20.abc.-5.0.20.7x.10.30.40']));

        $this->get()->shouldReturn([20, 10, 30]);
    }

    function it_puts_the_viewed_product_first_and_sends_the_cookie(ProductInterface $product): void
    {
        $request = new Request([], [], [], ['viewed_products' => '10.20.30']);
        $this->requestStack->push($request);
        $product->getId()->willReturn(20);

        $this->set($product);
        $this->get()->shouldReturn([20, 10, 30]);

        $response = new Response();
        $this->onKernelResponse($this->responseEvent($request, $response, HttpKernelInterface::MAIN_REQUEST));

        $cookie = $this->sentCookie($response);
        if ('20.10.30' !== $cookie->getValue()) {
            throw new \RuntimeException(sprintf('Cookie value "%s", expected "20.10.30".', (string) $cookie->getValue()));
        }
        if (!$cookie->isHttpOnly() || Cookie::SAMESITE_LAX !== $cookie->getSameSite() || '/' !== $cookie->getPath()) {
            throw new \RuntimeException('Cookie must be HttpOnly, SameSite=Lax, path "/".');
        }
        if (abs($cookie->getExpiresTime() - (time() + 3600)) > 5) {
            throw new \RuntimeException('Cookie must live for the configured lifetime.');
        }
    }

    function it_drops_the_oldest_product_over_the_limit(ProductInterface $product): void
    {
        $this->requestStack->push(new Request([], [], [], ['viewed_products' => '10.20.30']));
        $product->getId()->willReturn(40);

        $this->set($product);

        $this->get()->shouldReturn([40, 10, 20]);
    }

    function it_does_not_send_the_cookie_when_nothing_changed(): void
    {
        $request = new Request([], [], [], ['viewed_products' => '10.20']);
        $this->requestStack->push($request);

        $response = new Response();
        $this->onKernelResponse($this->responseEvent($request, $response, HttpKernelInterface::MAIN_REQUEST));

        if ([] !== $response->headers->getCookies()) {
            throw new \RuntimeException('No cookie must be sent when the list did not change.');
        }
    }

    function it_does_not_send_the_cookie_on_a_sub_request(ProductInterface $product): void
    {
        $request = new Request();
        $this->requestStack->push($request);
        $product->getId()->willReturn(10);
        $this->set($product);

        $response = new Response();
        $this->onKernelResponse($this->responseEvent($request, $response, HttpKernelInterface::SUB_REQUEST));

        if ([] !== $response->headers->getCookies()) {
            throw new \RuntimeException('A sub-request must not send the cookie.');
        }
    }

    function it_forgets_the_unsent_list_on_reset(ProductInterface $product): void
    {
        $this->requestStack->push(new Request());
        $product->getId()->willReturn(10);
        $this->set($product);

        $this->reset();

        $this->get()->shouldReturn([]);
    }

    private function responseEvent(Request $request, Response $response, int $type): ResponseEvent
    {
        $kernel = new class() implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };

        return new ResponseEvent($kernel, $request, $type, $response);
    }

    private function sentCookie(Response $response): Cookie
    {
        $cookies = $response->headers->getCookies();
        if (1 !== count($cookies) || 'viewed_products' !== $cookies[0]->getName()) {
            throw new \RuntimeException('Exactly one "viewed_products" cookie must be sent.');
        }

        return $cookies[0];
    }
}
