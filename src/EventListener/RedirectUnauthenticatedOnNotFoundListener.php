<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Redirects unauthenticated users to the login page when a route is not found,
 * instead of showing a 404 page. Authenticated users still see the 404.
 *
 * NotFoundHttpException is thrown by RouterListener BEFORE the firewall runs,
 * so security.yaml access_control cannot catch these cases.
 */
#[AsEventListener]
final readonly class RedirectUnauthenticatedOnNotFoundListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof NotFoundHttpException) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if ($token instanceof TokenInterface && $token->getUser() instanceof UserInterface) {
            return;
        }

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('app_login'),
        ));
    }
}
