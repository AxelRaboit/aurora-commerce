<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Manager\Decorator;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Auth\Manager\PasswordResetManagerInterface;
use Aurora\Core\Auth\Entity\ResetPasswordRequest;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator(decorates: PasswordResetManagerInterface::class)]
final readonly class AuditPasswordResetManagerDecorator implements PasswordResetManagerInterface
{
    public function __construct(
        #[AutowireDecorated]
        private PasswordResetManagerInterface $inner,
        private AuditLogger $auditLogger,
    ) {}

    public function sendResetLink(string $email): void
    {
        $this->inner->sendResetLink($email);
        $this->auditLogger->log('core', 'password_reset.link_requested', null, null, ['email' => $email]);
    }

    public function validateToken(string $selector, string $token): ?ResetPasswordRequest
    {
        return $this->inner->validateToken($selector, $token);
    }

    public function resetPassword(ResetPasswordRequest $resetRequest, string $newPassword): void
    {
        $this->inner->resetPassword($resetRequest, $newPassword);
        $this->auditLogger->log('core', 'password_reset.completed', 'User', $resetRequest->getUser()->getId(), [
            'email' => $resetRequest->getUser()->getEmail(),
        ]);
    }
}
