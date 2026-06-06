<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\EventSubscriber;

use Aurora\Module\Ecommerce\EcommerceContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Gates ecommerce routes against the admin/front toggles.
 *
 * Two independent prefix groups:
 *  - admin (`ecommerce_*`)              → 404 when EcommerceEnabled is off
 *  - front (`front_shop`, `front_cart`, …) → 404 when EcommerceShopEnabled is off
 *
 * Runs early (priority 16) so even POST endpoints get blocked cleanly without hitting
 * the cart/order managers.
 */
final readonly class EcommerceRouteGateSubscriber implements EventSubscriberInterface
{
    /** @var array<int, string> */
    private const array ADMIN_PREFIXES = ['backend_ecommerce_'];

    /** @var array<int, string> */
    private const array FRONT_PREFIXES = [
        'frontend_shop',
        'frontend_cart',
        'frontend_checkout',
        'frontend_order_show',
        'frontend_account_orders',
    ];

    public function __construct(private EcommerceContext $ecommerceContext) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 0]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = (string) $event->getRequest()->attributes->get('_route', '');
        if ('' === $route) {
            return;
        }

        if ($this->matchesAny($route, self::ADMIN_PREFIXES) && !$this->ecommerceContext->isBackendEnabled()) {
            throw new NotFoundHttpException();
        }

        if ($this->matchesAny($route, self::FRONT_PREFIXES) && !$this->ecommerceContext->isFrontEnabled()) {
            throw new NotFoundHttpException();
        }
    }

    /** @param array<int, string> $prefixes */
    private function matchesAny(string $route, array $prefixes): bool
    {
        return array_any($prefixes, fn ($prefix): bool => str_starts_with($route, (string) $prefix));
    }
}
