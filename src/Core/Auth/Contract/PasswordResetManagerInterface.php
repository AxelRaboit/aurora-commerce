<?php

declare(strict_types=1);

namespace App\Core\Auth\Contract;

use App\Core\Auth\Entity\ResetPasswordRequest;

interface PasswordResetManagerInterface
{
    public function sendResetLink(string $email): void;

    public function validateToken(string $selector, string $token): ?ResetPasswordRequest;

    public function resetPassword(ResetPasswordRequest $resetRequest, string $newPassword): void;
}
