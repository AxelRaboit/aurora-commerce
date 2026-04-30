<?php

declare(strict_types=1);

namespace Aurora\Core\Dashboard\View;

/**
 * Builds the Twig payload for the admin dashboard landing page. The current
 * payload is empty but the builder is kept as a seam so future widgets can be
 * added without re-introducing payload logic in the controller.
 */
final readonly class DashboardViewBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function indexView(): array
    {
        return [];
    }
}
