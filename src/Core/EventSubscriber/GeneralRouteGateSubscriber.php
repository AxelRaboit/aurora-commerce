<?php

declare(strict_types=1);

namespace Aurora\Core\EventSubscriber;

use Aurora\Core\Service\GeneralContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Handles the "Général" section route gating. Currently only the
 * dashboard is gated: when masked (globally or per-user), any hit
 * on `backend_dashboard` is redirected to `backend_profile` so the
 * user always lands on something they can read instead of a 404.
 *
 * Why redirect (vs 404 like other gates): the Dashboard is the
 * post-login landing page. Greeting an authenticated user with an
 * error feels wrong; sending them to their profile is a softer,
 * predictable fallback.
 */
final readonly class GeneralRouteGateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private GeneralContext $generalContext,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

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
        if ('backend_dashboard' !== $route) {
            return;
        }

        if ($this->generalContext->isDashboardEnabled()) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('backend_profile')));
    }
}
