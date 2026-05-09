<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\EventSubscriber;

use Aurora\Module\Crm\Service\CrmContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * 404s every CRM admin route (`crm_*`) when the CrmEnabled setting is off.
 * CRM has no front routes, so a single prefix list is enough.
 */
final readonly class CrmRouteGateSubscriber implements EventSubscriberInterface
{
    private const string ADMIN_PREFIX = 'backend_crm_';

    public function __construct(private CrmContext $crmContext) {}

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

        if (!$this->crmContext->isAdminEnabled()) {
            throw new NotFoundHttpException();
        }
    }
}
