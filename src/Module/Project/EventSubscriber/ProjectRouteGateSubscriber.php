<?php

declare(strict_types=1);

namespace Aurora\Module\Project\EventSubscriber;

use Aurora\Module\Project\Service\ProjectContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * 404s every Project admin route (`backend_projects*`) when the ProjectAdminEnabled setting is off.
 */
final readonly class ProjectRouteGateSubscriber implements EventSubscriberInterface
{
    private const string ADMIN_PREFIX = 'backend_projects';

    public function __construct(private ProjectContext $projectContext) {}

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

        if (!$this->projectContext->isAdminEnabled()) {
            throw new NotFoundHttpException();
        }
    }
}
