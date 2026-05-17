<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Auth\View;

/**
 * Builds the Twig payloads for the admin forgot- and reset-password pages.
 * Centralises the status / errors / token shape so the controller stays
 * focused on validation and persistence.
 */
final readonly class PasswordResetViewBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function forgotView(?string $status): array
    {
        return ['status' => $status];
    }

    /**
     * @param array<string, string> $errors
     *
     * @return array<string, mixed>
     */
    public function resetView(string $selector, string $token, array $errors = []): array
    {
        return [
            'errors' => $errors,
            'selector' => $selector,
            'token' => $token,
        ];
    }
}
