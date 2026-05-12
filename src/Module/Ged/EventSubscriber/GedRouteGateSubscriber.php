<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\EventSubscriber;

use Aurora\Module\Ged\Service\GedContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class GedRouteGateSubscriber implements EventSubscriberInterface
{
    public function __construct(private GedContext $gedContext) {}

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

        if (str_starts_with($route, 'backend_ged_') && !$this->gedContext->isBackendEnabled()) {
            throw new NotFoundHttpException();
        }

        if (str_starts_with($route, 'frontend_ged_') && !$this->gedContext->isFrontendEnabled()) {
            throw new NotFoundHttpException();
        }
    }
}
