<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Overview\Service;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;

interface PersonalFinanceOverviewServiceInterface
{
    /**
     * Cross-wallet snapshot for the Overview page. Same idempotent
     * stateless contract as PersonalFinanceDashboardServiceInterface —
     * every call rebuilds from scratch.
     *
     * @return array<string, mixed>
     */
    public function snapshot(CoreUserInterface $user, ?DateTimeImmutable $today = null): array;
}
