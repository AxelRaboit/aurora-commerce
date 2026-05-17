<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Auth\EventListener;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Platform\User\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final readonly class AuditAuthEventListener
{
    public function __construct(private AuditLogger $auditLogger) {}

    #[AsEventListener]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getAuthenticatedToken()->getUser();
        $id = $user instanceof User ? $user->getId() : null;
        $email = $user instanceof User ? $user->getEmail() : $user?->getUserIdentifier();

        $this->auditLogger->log('core', 'auth.login', 'User', $id, ['email' => $email]);
    }

    #[AsEventListener]
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $this->auditLogger->log('core', 'auth.login_failed', null, null, [
            'email' => $event->getRequest()->request->get('email') ?? $event->getRequest()->request->get('_username'),
        ]);
    }

    #[AsEventListener]
    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        $user = $token?->getUser();
        $id = $user instanceof User ? $user->getId() : null;
        $email = $user instanceof User ? $user->getEmail() : $user?->getUserIdentifier();

        $this->auditLogger->log('core', 'auth.logout', 'User', $id, ['email' => $email]);
    }
}
