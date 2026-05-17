<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Auth\Manager;

use Aurora\Core\Platform\User\Entity\CoreUserInterface;

interface EmailVerificationManagerInterface
{
    public function generateToken(CoreUserInterface $user): string;

    public function sendVerificationEmail(CoreUserInterface $user, string $verifyUrl): void;
}
