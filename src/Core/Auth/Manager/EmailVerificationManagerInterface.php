<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Manager;

use Aurora\Core\User\Entity\User;

interface EmailVerificationManagerInterface
{
    public function generateToken(User $user): string;

    public function sendVerificationEmail(User $user, string $verifyUrl): void;
}
