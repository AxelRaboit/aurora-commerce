<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Auth\View;

use Aurora\Module\Platform\User\Entity\User;

/**
 * Builds the Twig payload for the admin invitation acceptance page. Centralises
 * the user / token / errors shape so the controller stays focused on auth flow.
 */
final readonly class InvitationViewBuilder
{
    /**
     * @param array<string, string> $errors
     *
     * @return array<string, mixed>
     */
    public function acceptView(User $user, string $selector, string $token, array $errors = []): array
    {
        return [
            'user' => $user,
            'selector' => $selector,
            'token' => $token,
            'errors' => $errors,
        ];
    }
}
