<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\User\Manager\Frontend;

use Aurora\Module\Platform\Auth\Dto\Frontend\RegisterInput;
use Aurora\Module\Platform\Auth\Entity\ResetPasswordRequest;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Platform\User\Entity\User;

interface UserManagerInterface
{
    public function register(RegisterInput $input): CoreUserInterface;

    public function sendVerificationEmail(CoreUserInterface $user, string $locale): void;

    public function verifyEmail(string $token): ?CoreUserInterface;

    public function updateProfile(User $user, string $name, ?string $newPassword = null): void;

    public function deleteAccount(User $user): void;

    public function resendVerificationEmail(string $email, string $locale): void;

    public function sendPasswordResetEmail(string $email, string $locale): void;

    public function validateResetToken(string $selector, string $token): ?ResetPasswordRequest;

    public function resetPassword(ResetPasswordRequest $resetRequest, string $newPassword): void;
}
