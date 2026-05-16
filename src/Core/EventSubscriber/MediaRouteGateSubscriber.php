<?php

declare(strict_types=1);

namespace Aurora\Core\EventSubscriber;

use Aurora\Core\Service\MediaContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * 404s the Media admin routes (`backend_media*`) when the Media toggle
 * is OFF — globally or for the current user. Sibling of
 * {@see PlatformRouteGateSubscriber}; split out of it in Jalon 4.5 so
 * route gating mirrors the module split.
 */
final readonly class MediaRouteGateSubscriber implements EventSubscriberInterface
{
    /** Map of route prefix → callable returning bool (false ⇒ 404). */
    private array $gates;

    public function __construct(private MediaContext $mediaContext)
    {
        $this->gates = [
            'backend_media' => $this->mediaContext->isLibraryEnabled(...),
        ];
    }

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

        foreach ($this->gates as $prefix => $isEnabled) {
            if (str_starts_with($route, $prefix) && !$isEnabled()) {
                throw new NotFoundHttpException();
            }
        }
    }
}
