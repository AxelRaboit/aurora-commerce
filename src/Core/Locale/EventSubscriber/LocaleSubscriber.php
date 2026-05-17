<?php

declare(strict_types=1);

namespace Aurora\Core\Locale\EventSubscriber;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Core\Platform\User\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

final readonly class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LocaleContextInterface $localeContext,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        if ($this->localeContext->isSingleLocaleMode()) {
            return;
        }

        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof User) {
            $event->getRequest()->getSession()->set('_locale', $user->getLocale()->value);
        }
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->localeContext->isSingleLocaleMode()) {
            $request->setLocale($this->localeContext->getDefaultLocale());

            return;
        }

        $locale = $request->getSession()->get('_locale', LocaleEnum::default()->value);

        if (!LocaleEnum::isSupported($locale)) {
            $locale = LocaleEnum::default()->value;
        }

        $request->setLocale($locale);
    }
}
