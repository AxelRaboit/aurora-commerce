<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Auth\Manager;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;

interface EmailVerificationManagerInterface
{
    public function generateToken(CoreUserInterface $user): string;

    public function sendVerificationEmail(CoreUserInterface $user, string $verifyUrl): void;
}
