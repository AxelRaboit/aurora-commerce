<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Auth\View;

/**
 * Builds the Twig payloads for the admin access-request page. Keeps the
 * enabled-flag / errors / values shape in one place so the controller stays
 * focused on HTTP flow.
 */
final readonly class AccessRequestViewBuilder
{
    /**
     * @param array<string, string> $errors
     * @param array<string, mixed>  $values
     *
     * @return array<string, mixed>
     */
    public function formView(bool $accessRequestEnabled, array $errors = [], array $values = []): array
    {
        return [
            'accessRequestEnabled' => $accessRequestEnabled,
            'errors' => $errors,
            'values' => $values,
        ];
    }
}
