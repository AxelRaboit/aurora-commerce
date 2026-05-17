<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Auth\Manager;

use Aurora\Module\Platform\User\Entity\User;

interface InvitationManagerInterface
{
    public function sendInvitation(User $user, string $plainToken, ?string $customMessage): void;
}
