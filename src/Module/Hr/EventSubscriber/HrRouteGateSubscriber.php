<?php

declare(strict_types=1);

namespace Aurora\Module\Hr\EventSubscriber;

use Aurora\Module\Hr\Service\HrContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * 404s every HR admin route (`backend_hr_*`) when the HrEnabled setting is off.
 */
final readonly class HrRouteGateSubscriber implements EventSubscriberInterface
{
    private const string ADMIN_PREFIX = 'backend_hr_';

    public function __construct(private HrContext $hrContext) {}

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

        if (!$this->hrContext->isAdminEnabled()) {
            throw new NotFoundHttpException();
        }
    }
}
