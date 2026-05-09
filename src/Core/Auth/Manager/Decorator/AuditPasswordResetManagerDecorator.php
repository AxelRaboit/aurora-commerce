<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Manager\Decorator;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Auth\Entity\ResetPasswordRequest;
use Aurora\Core\Auth\Manager\PasswordResetManagerInterface;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserTypeEnum;
use DateTimeImmutable;
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

    /**
     * @return array{selector: string, plainToken: string, expiresAt: DateTimeImmutable}
     */
    public function createRequestForUser(User $user): array
    {
        return $this->inner->createRequestForUser($user);
    }

    public function sendResetEmail(User $user, string $resetUrl, ?DateTimeImmutable $expiresAt = null): void
    {
        $this->inner->sendResetEmail($user, $resetUrl, $expiresAt);
    }

    public function validateToken(string $selector, string $token, ?UserTypeEnum $expectedType = UserTypeEnum::Backend): ?ResetPasswordRequest
    {
        return $this->inner->validateToken($selector, $token, $expectedType);
    }

    public function resetPassword(ResetPasswordRequest $resetRequest, string $newPassword): void
    {
        $this->inner->resetPassword($resetRequest, $newPassword);
        $this->auditLogger->log('core', 'password_reset.completed', 'User', $resetRequest->getUser()->getId(), [
            'email' => $resetRequest->getUser()->getEmail(),
        ]);
    }
}
