<?php

declare(strict_types=1);

namespace App\Module\Ecommerce\EventSubscriber;

use App\Module\Ecommerce\Service\EcommerceContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Gates ecommerce routes against the admin/front toggles.
 *
 * Two independent prefix groups:
 *  - admin (`ecommerce_*`)              → 404 when EcommerceAdminEnabled is off
 *  - front (`front_shop`, `front_cart`, …) → 404 when EcommerceFrontEnabled is off
 *
 * Runs early (priority 16) so even POST endpoints get blocked cleanly without hitting
 * the cart/order managers.
 */
final readonly class EcommerceRouteGateSubscriber implements EventSubscriberInterface
{
    /** @var array<int, string> */
    private const array ADMIN_PREFIXES = ['ecommerce_'];

    /** @var array<int, string> */
    private const array FRONT_PREFIXES = [
        'front_shop',
        'front_cart',
        'front_checkout',
        'front_order_show',
        'front_account_orders',
    ];

    public function __construct(private EcommerceContext $ecommerceContext) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 16]];
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

        if ($this->matchesAny($route, self::ADMIN_PREFIXES) && !$this->ecommerceContext->isAdminEnabled()) {
            throw new NotFoundHttpException();
        }

        if ($this->matchesAny($route, self::FRONT_PREFIXES) && !$this->ecommerceContext->isFrontEnabled()) {
            throw new NotFoundHttpException();
        }
    }

    /** @param array<int, string> $prefixes */
    private function matchesAny(string $route, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($route, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
