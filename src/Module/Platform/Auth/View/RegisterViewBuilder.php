<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Auth\View;

/**
 * Builds the Twig payloads for the admin self-service register flow. Keeps
 * the registration / confirmation / verification shapes in one place so the
 * controller stays focused on persistence.
 */
final readonly class RegisterViewBuilder
{
    /**
     * @param array<string, string> $errors
     * @param array<string, mixed>  $values
     *
     * @return array<string, mixed>
     */
    public function registerView(bool $registrationEnabled, array $errors = [], array $values = []): array
    {
        return [
            'registrationEnabled' => $registrationEnabled,
            'errors' => $errors,
            'values' => $values,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function confirmView(?string $pendingEmail, bool $resent): array
    {
        return [
            'pendingEmail' => $pendingEmail,
            'resent' => $resent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyView(bool $success): array
    {
        return ['success' => $success];
    }
}
