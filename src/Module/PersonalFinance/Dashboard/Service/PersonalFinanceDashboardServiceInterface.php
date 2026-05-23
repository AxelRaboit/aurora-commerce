<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Dashboard\Service;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;

interface PersonalFinanceDashboardServiceInterface
{
    /**
     * Builds the aggregated dashboard payload for a user — KPI tiles +
     * sparkline + top categories + pinned wallets + recent transactions
     * + active goals + upcoming recurring + budget alerts.
     *
     * @return array<string, mixed>
     */
    public function snapshot(CoreUserInterface $user, ?DateTimeImmutable $today = null): array;
}
