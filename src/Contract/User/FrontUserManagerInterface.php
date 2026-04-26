<?php

declare(strict_types=1);

namespace App\Contract\User;

use App\DTO\Auth\FrontRegisterInput;
use App\Entity\ResetPasswordRequest;
use App\Entity\User;

interface FrontUserManagerInterface
{
    public function register(FrontRegisterInput $input): User;

    public function sendVerificationEmail(User $user, string $locale = 'fr'): void;

    public function verifyEmail(string $token): ?User;

    public function updateProfile(User $user, string $name, ?string $newPassword = null): void;

    public function deleteAccount(User $user): void;

    public function resendVerificationEmail(string $email, string $locale): void;

    public function sendPasswordResetEmail(string $email, string $locale): void;

    public function validateResetToken(string $selector, string $token): ?ResetPasswordRequest;

    public function resetPassword(ResetPasswordRequest $resetRequest, string $newPassword): void;
}
