<?php

declare(strict_types=1);

namespace Aurora\Core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Enables Symfony's X-Sendfile trust at the start of every request so
 * any `BinaryFileResponse` produced by a serve controller can be
 * offloaded to Apache (`mod_xsendfile`) or nginx (`X-Accel-Redirect`)
 * in production.
 *
 * Dev/test without the module installed → Symfony falls back to
 * `readfile()` automatically; calling this method is harmless. The
 * subscriber runs at the highest priority so even an earlier filter
 * that returns a `BinaryFileResponse` benefits from the optimisation.
 *
 * See `docs/aurora-core/dev/storage_policy.md` for the Apache config
 * snippet (`a2enmod xsendfile` + `XSendFilePath`).
 */
final readonly class XSendfileBootSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 1024]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        BinaryFileResponse::trustXSendfileTypeHeader();
    }
}
