<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Sync;

use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Planning\Event\Enum\PlanningEventStatusEnum;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use DateTimeImmutable;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched by any module that wants something to appear on a planning.
 * The Planning module's PlanningSyncSubscriber picks it up and upserts a
 * PlanningEvent identified by (sourceType, sourceId).
 *
 * Re-dispatching the same (sourceType, sourceId) updates the existing
 * PlanningEvent in place — safe to re-emit on every change.
 */
final class EntityScheduledEvent extends Event
{
    /**
     * @param list<CoreUserInterface> $attendees
     */
    public function __construct(
        public readonly string $sourceType,
        public readonly int $sourceId,
        public readonly PlanningInterface $planning,
        public readonly string $title,
        public readonly DateTimeImmutable $startAt,
        public readonly DateTimeImmutable $endAt,
        public readonly bool $allDay = false,
        public readonly ?string $description = null,
        public readonly ?string $location = null,
        public readonly ?string $sourceLabel = null,
        public readonly PlanningEventStatusEnum $status = PlanningEventStatusEnum::Confirmed,
        public readonly array $attendees = [],
    ) {}
}
