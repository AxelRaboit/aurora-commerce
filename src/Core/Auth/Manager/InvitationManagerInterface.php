<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Manager;

use Aurora\Core\User\Entity\User;

interface InvitationManagerInterface
{
    public function sendInvitation(User $user, string $plainToken, ?string $customMessage): void;
}
