<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\EventSubscriber;

use Aurora\Module\Billing\Service\BillingContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * 404s every Billing admin route (`billing_*`) when BillingEnabled is off.
 */
final readonly class BillingRouteGateSubscriber implements EventSubscriberInterface
{
    private const string ADMIN_PREFIX = 'backend_billing_';

    public function __construct(private BillingContext $billingContext) {}

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
        if ('' === $route || !str_starts_with($route, self::ADMIN_PREFIX)) {
            return;
        }

        if (!$this->billingContext->isBackendEnabled()) {
            throw new NotFoundHttpException();
        }
    }
}
