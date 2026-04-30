<?php

declare(strict_types=1);

namespace Aurora\Core\Dashboard\View;

use Aurora\Core\Dashboard\Service\AdminStatsService;

/**
 * Builds the Twig payload for the dev overview tab. Wraps AdminStatsService
 * so the controller stays focused on flow (XHR vs full page rendering).
 */
final readonly class OverviewViewBuilder
{
    public function __construct(private AdminStatsService $statsService) {}

    /**
     * @return array<string, mixed>
     */
    public function overviewPayload(): array
    {
        return ['stats' => $this->statsService->getStats()];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function indexView(array $payload): array
    {
        return [
            'tab' => 'overview',
            'stats' => $payload['stats'],
        ];
    }
}
