<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Statistics\Service;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;

interface PersonalFinanceStatisticsServiceInterface
{
    /**
     * Statistics page payload covering the trailing N months (3, 6
     * or 12 — clamped to that set). Stateless : every call rebuilds
     * from scratch from the underlying transaction store.
     *
     * @return array<string, mixed>
     */
    public function snapshot(CoreUserInterface $user, int $months = 6, ?DateTimeImmutable $today = null): array;
}
