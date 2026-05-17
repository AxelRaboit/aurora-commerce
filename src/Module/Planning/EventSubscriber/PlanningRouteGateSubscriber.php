<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\EventSubscriber;

use Aurora\Module\Planning\PlanningContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * 404s every Planning admin route (`backend_plannings*`) when the PlanningEnabled setting is off.
 */
final readonly class PlanningRouteGateSubscriber implements EventSubscriberInterface
{
    private const string ADMIN_PREFIX = 'backend_plannings';

    public function __construct(private PlanningContext $planningContext) {}

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

        if (!$this->planningContext->isBackendEnabled()) {
            throw new NotFoundHttpException();
        }
    }
}
