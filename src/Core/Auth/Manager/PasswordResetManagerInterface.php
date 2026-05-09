<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Manager;

use Aurora\Core\Auth\Entity\ResetPasswordRequest;

interface PasswordResetManagerInterface
{
    public function sendResetLink(string $email): void;

    public function validateToken(string $selector, string $token): ?ResetPasswordRequest;

    public function resetPassword(ResetPasswordRequest $resetRequest, string $newPassword): void;
}
