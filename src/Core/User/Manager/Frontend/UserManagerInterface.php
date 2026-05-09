<?php

declare(strict_types=1);

namespace Aurora\Core\User\Manager\Frontend;

use Aurora\Core\Auth\Dto\Frontend\RegisterInput;
use Aurora\Core\Auth\Entity\ResetPasswordRequest;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Core\User\Entity\User;

interface UserManagerInterface
{
    public function register(RegisterInput $input): User;

    public function sendVerificationEmail(User $user, string $locale = 'fr'): void;

    public function verifyEmail(string $token): ?CoreUserInterface;

    public function updateProfile(User $user, string $name, ?string $newPassword = null): void;

    public function deleteAccount(User $user): void;

    public function resendVerificationEmail(string $email, string $locale): void;

    public function sendPasswordResetEmail(string $email, string $locale): void;

    public function validateResetToken(string $selector, string $token): ?ResetPasswordRequest;

    public function resetPassword(ResetPasswordRequest $resetRequest, string $newPassword): void;
}
