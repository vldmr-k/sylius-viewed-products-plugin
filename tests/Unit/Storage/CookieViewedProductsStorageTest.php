<?php

declare(strict_types=1);

namespace Tests\Vldmrk\SyliusViewedProductsPlugin\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Vldmrk\SyliusViewedProductsPlugin\Storage\CookieViewedProductsStorage;

final class CookieViewedProductsStorageTest extends TestCase
{
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
    }

    public function testItListensToTheResponse(): void
    {
        self::assertSame([KernelEvents::RESPONSE => 'onKernelResponse'], CookieViewedProductsStorage::getSubscribedEvents());
    }

    public function testNoRequestOrNoCookieGivesAnEmptyList(): void
    {
        $storage = $this->storage();
        self::assertSame([], $storage->get());

        $this->requestStack->push(new Request([], [], [], ['viewed_products' => '']));
        self::assertSame([], $storage->get());
    }

    public function testTheListIsReadFromTheDottedCookie(): void
    {
        $this->requestStack->push(new Request([], [], [], ['viewed_products' => '812.77.abc.77.4031']));

        self::assertSame([812, 77, 4031], $this->storage()->get());
        self::assertSame([812, 77, 4031], $this->storage()->all());
    }

    public function testTheCookieOfTheMainRequestIsRead(): void
    {
        $this->requestStack->push(new Request([], [], [], ['viewed_products' => '1.2']));
        $this->requestStack->push(new Request([], [], [], ['viewed_products' => '9']));

        self::assertSame([1, 2], $this->storage()->get());
    }

    public function testTheCookieNameAndLimitComeFromTheConfig(): void
    {
        $this->requestStack->push(new Request([], [], [], ['viewed_products' => '5', 'seen' => '1.2.3.4']));

        self::assertSame([1, 2], $this->storage(2, 'seen')->get());
    }

    public function testAViewChangesTheListForTheRestOfTheRequest(): void
    {
        $this->requestStack->push(new Request([], [], [], ['viewed_products' => '10.20.30']));
        $storage = $this->storage(3);

        $storage->set($this->product(30));
        self::assertSame([30, 10, 20], $storage->get());

        $storage->set($this->product(40));
        self::assertSame([40, 30, 10], $storage->get());
    }

    public function testTheChangedListGoesOutAsTheCookieOfTheMainResponse(): void
    {
        $request = Request::create('https://pmpro.com.ua/uk/products/x', 'GET', [], ['viewed_products' => '10.20']);
        $this->requestStack->push($request);
        $storage = $this->storage(20, 'viewed_products', 3600);
        $storage->set($this->product(20));

        $response = new Response();
        $before = time();
        $storage->onKernelResponse($this->event($request, $response, HttpKernelInterface::MAIN_REQUEST));

        $cookies = $response->headers->getCookies();
        self::assertCount(1, $cookies);
        $cookie = $cookies[0];
        self::assertSame('viewed_products', $cookie->getName());
        self::assertSame('20.10', $cookie->getValue());
        self::assertSame('/', $cookie->getPath());
        self::assertNull($cookie->getDomain());
        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertFalse($cookie->isRaw());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        self::assertGreaterThanOrEqual($before + 3600, $cookie->getExpiresTime());
        self::assertLessThanOrEqual(time() + 3600, $cookie->getExpiresTime());
    }

    public function testTheCookieIsNotSecureOverHttp(): void
    {
        $request = Request::create('http://localhost/');
        $this->requestStack->push($request);
        $storage = $this->storage();
        $storage->set($this->product(1));

        $response = new Response();
        $storage->onKernelResponse($this->event($request, $response, HttpKernelInterface::MAIN_REQUEST));

        self::assertFalse($response->headers->getCookies()[0]->isSecure());
    }

    public function testTheCookieIsSentOnceAndTheRequestCookieIsReadAgainAfterwards(): void
    {
        $request = new Request([], [], [], ['viewed_products' => '10']);
        $this->requestStack->push($request);
        $storage = $this->storage();
        $storage->set($this->product(20));

        $storage->onKernelResponse($this->event($request, new Response(), HttpKernelInterface::MAIN_REQUEST));
        $second = new Response();
        $storage->onKernelResponse($this->event($request, $second, HttpKernelInterface::MAIN_REQUEST));

        self::assertSame([], $second->headers->getCookies());
        self::assertSame([10], $storage->get());
    }

    public function testNothingIsSentWithoutAView(): void
    {
        $request = new Request([], [], [], ['viewed_products' => '10']);
        $this->requestStack->push($request);

        $response = new Response();
        $this->storage()->onKernelResponse($this->event($request, $response, HttpKernelInterface::MAIN_REQUEST));

        self::assertSame([], $response->headers->getCookies());
    }

    public function testASubRequestResponseDoesNotCarryTheCookieNorClearTheChange(): void
    {
        $request = new Request();
        $this->requestStack->push($request);
        $storage = $this->storage();
        $storage->set($this->product(5));

        $response = new Response();
        $storage->onKernelResponse($this->event($request, $response, HttpKernelInterface::SUB_REQUEST));

        self::assertSame([], $response->headers->getCookies());
        self::assertSame([5], $storage->get());
    }

    public function testResetForgetsTheUnsentChange(): void
    {
        $this->requestStack->push(new Request([], [], [], ['viewed_products' => '10']));
        $storage = $this->storage();
        $storage->set($this->product(20));

        $storage->reset();

        self::assertSame([10], $storage->get());
    }

    private function storage(int $limit = 20, string $name = 'viewed_products', int $lifetime = 2592000): CookieViewedProductsStorage
    {
        return new CookieViewedProductsStorage($this->requestStack, $limit, $name, $lifetime);
    }

    private function event(Request $request, Response $response, int $type): ResponseEvent
    {
        return new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, $type, $response);
    }

    private function product(int $id): ProductInterface
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn($id);

        return $product;
    }
}
