<?php

declare(strict_types=1);

namespace Aurora\Core\EventSubscriber;

use Aurora\Core\Service\PlatformContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * 404s the Platform admin routes (Media, Users, Agencies, Services,
 * Settings, Themes) when their toggle is OFF — globally or for the
 * current user.
 *
 * The Dashboard (`backend_dashboard`) is intentionally not gated here:
 * it is the post-login landing page and is always accessible.
 */
final readonly class PlatformRouteGateSubscriber implements EventSubscriberInterface
{
    /** Map of route prefix → callable returning bool (false ⇒ 404). */
    private array $gates;

    public function __construct(private PlatformContext $platformContext)
    {
        $this->gates = [
            'backend_media' => $this->platformContext->isMediaEnabled(...),
            'backend_users' => $this->platformContext->isUsersEnabled(...),
            'backend_agencies' => $this->platformContext->isAgenciesEnabled(...),
            'backend_services' => $this->platformContext->isServicesEnabled(...),
            'backend_settings' => $this->platformContext->isSettingsEnabled(...),
            'backend_themes' => $this->platformContext->isThemesEnabled(...),
        ];
    }

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

        foreach ($this->gates as $prefix => $isEnabled) {
            if (str_starts_with($route, $prefix) && !$isEnabled()) {
                throw new NotFoundHttpException();
            }
        }
    }
}
