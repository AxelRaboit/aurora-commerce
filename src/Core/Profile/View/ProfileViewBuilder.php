<?php

declare(strict_types=1);

namespace Aurora\Core\Profile\View;

use Aurora\Core\User\Entity\User;

/**
 * Builds the Twig payload for the admin profile page. Currently exposes the
 * mood-message length cap, kept as a service so future profile widgets can
 * grow without re-introducing payload logic in the controller.
 */
final readonly class ProfileViewBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function indexView(): array
    {
        return [
            'moodMessageMaxLength' => User::MOOD_MESSAGE_MAX_LENGTH,
        ];
    }
}
