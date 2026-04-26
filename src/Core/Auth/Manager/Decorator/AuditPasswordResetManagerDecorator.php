<?php

declare(strict_types=1);

namespace App\Core\Auth\Manager\Decorator;

use App\Core\Auth\Contract\PasswordResetManagerInterface;
use App\Core\Auth\Entity\ResetPasswordRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator(decorates: PasswordResetManagerInterface::class)]
final readonly class AuditPasswordResetManagerDecorator implements PasswordResetManagerInterface
{
    public function __construct(
        #[AutowireDecorated]
        private PasswordResetManagerInterface $inner,
        private LoggerInterface $logger,
    ) {}

    public function sendResetLink(string $email): void
    {
        $this->inner->sendResetLink($email);

        $this->logger->info('password_reset.link_requested', ['email' => $email]);
    }

    public function validateToken(string $selector, string $token): ?ResetPasswordRequest
    {
        return $this->inner->validateToken($selector, $token);
    }

    public function resetPassword(ResetPasswordRequest $resetRequest, string $newPassword): void
    {
        $this->inner->resetPassword($resetRequest, $newPassword);

        $this->logger->warning('password_reset.completed', [
            'email' => $resetRequest->getUser()->getEmail(),
            'userId' => $resetRequest->getUser()->getId(),
        ]);
    }
}
