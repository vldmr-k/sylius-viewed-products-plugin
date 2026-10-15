<?php

declare(strict_types=1);

namespace Vldmrk\SyliusViewedProductsPlugin\Storage;

use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Keeps the list in a cookie of its own, ids joined by dots ("812.77.4031"): viewing products
 * does not start a session. The list is read from the request cookie; a list changed during
 * the request goes out as the cookie of the main response, and every view extends its lifetime.
 */
final class CookieViewedProductsStorage implements ViewedProductsStorageInterface, EventSubscriberInterface, ResetInterface
{
    private const SEPARATOR = '.';

    /** @var int[]|null The list changed during this request, not yet sent. */
    private ?array $changed = null;

    public function __construct(
        private RequestStack $requestStack,
        private int $maxItems = 20,
        private string $cookieName = 'viewed_products',
        private int $cookieLifetime = 2592000,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function set(ProductInterface $product): void
    {
        $this->changed = ViewedProductIds::prepend($this->get(), (int) $product->getId(), $this->maxItems);
    }

    public function get(): array
    {
        if (null !== $this->changed) {
            return $this->changed;
        }

        $value = $this->requestStack->getMainRequest()?->cookies->get($this->cookieName);
        if (!is_string($value) || '' === $value) {
            return [];
        }

        return ViewedProductIds::normalize(explode(self::SEPARATOR, $value), $this->maxItems);
    }

    public function all(): array
    {
        return $this->get();
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || null === $this->changed) {
            return;
        }

        $event->getResponse()->headers->setCookie(Cookie::create(
            $this->cookieName,
            implode(self::SEPARATOR, $this->changed),
            time() + $this->cookieLifetime,
            '/',
            null,
            $event->getRequest()->isSecure(),
            true,
            false,
            Cookie::SAMESITE_LAX,
        ));

        $this->changed = null;
    }

    public function reset(): void
    {
        $this->changed = null;
    }
}
