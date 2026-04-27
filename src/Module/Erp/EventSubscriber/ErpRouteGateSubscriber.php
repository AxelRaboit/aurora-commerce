<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\EventSubscriber;

use Aurora\Module\Erp\Service\ErpContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * 404s every ERP admin route (`erp_*`) when the ErpAdminEnabled setting is off.
 * ERP has no front routes, so a single prefix list is enough.
 */
final readonly class ErpRouteGateSubscriber implements EventSubscriberInterface
{
    private const string ADMIN_PREFIX = 'erp_';

    public function __construct(private ErpContext $erpContext) {}

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
        if ('' === $route || !str_starts_with($route, self::ADMIN_PREFIX)) {
            return;
        }

        if (!$this->erpContext->isAdminEnabled()) {
            throw new NotFoundHttpException();
        }
    }
}
