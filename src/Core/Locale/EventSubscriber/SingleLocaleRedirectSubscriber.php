<?php

declare(strict_types=1);

namespace Aurora\Core\Locale\EventSubscriber;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Quand le mode mono-langue est actif, redirige toute URL préfixée par un code
 * locale ≠ default vers son équivalent sur la locale par défaut (301).
 *
 * Tourne après LocaleSubscriber (priorité 20) et avant le RouterListener de
 * Symfony (priorité 32 → exécuté plus tôt, mais priorité 32 < 20 ? Non : sur
 * KernelEvents::REQUEST, les priorités plus élevées tournent en premier. Donc
 * 18 < 20 : ce subscriber tourne juste après LocaleSubscriber. Le
 * RouterListener tourne avec priorité 32 → AVANT nous, mais avec priorité 16
 * → après, selon la version. Peu importe : on travaille uniquement sur le path
 * brut de la Request, indépendamment du routeur.
 */
final class SingleLocaleRedirectSubscriber implements EventSubscriberInterface
{
    private const LOCALE_PREFIX_PATTERN = '#^/([a-z]{2})(/.*|$)#';

    public function __construct(
        private readonly LocaleContextInterface $localeContext,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 18]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->localeContext->isSingleLocaleMode()) {
            return;
        }

        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        if (1 !== preg_match(self::LOCALE_PREFIX_PATTERN, $pathInfo, $matches)) {
            return;
        }

        $urlLocale = $matches[1];
        $defaultLocale = $this->localeContext->getDefaultLocale();

        if ($urlLocale === $defaultLocale) {
            return;
        }

        // On ne touche que les codes locale connus du bundle pour éviter de
        // capter par erreur des paths comme `/ab/foo` qui ne sont pas des locales.
        if (!\in_array($urlLocale, $this->localeContext->getAllLocales(), true)) {
            return;
        }

        $remainder = '' === $matches[2] ? '/' : $matches[2];
        $newPath = '/'.$defaultLocale.$remainder;

        $qs = $request->getQueryString();
        $target = $request->getBaseUrl().$newPath.(null !== $qs ? '?'.$qs : '');

        $event->setResponse(new RedirectResponse($target, 301));
    }
}
