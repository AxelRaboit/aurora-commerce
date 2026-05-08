<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Manager;

use Aurora\Module\Planning\Event\Dto\PlanningEventInputInterface;
use Aurora\Module\Planning\Event\Entity\PlanningEventInterface;
use Aurora\Module\Planning\Sync\EntityScheduledEvent;

interface PlanningEventManagerInterface
{
    public function create(PlanningEventInputInterface $input): PlanningEventInterface;

    public function update(PlanningEventInterface $event, PlanningEventInputInterface $input): void;

    /**
     * Idempotent upsert from a domain event.
     * If a PlanningEvent already exists for (sourceType, sourceId), it is
     * updated in place; otherwise a new one is created.
     */
    public function syncFromSource(EntityScheduledEvent $event): PlanningEventInterface;

    /**
     * Idempotent removal from a domain event. No-op if no PlanningEvent
     * matches (sourceType, sourceId).
     */
    public function removeBySource(string $sourceType, int $sourceId): void;

    public function delete(PlanningEventInterface $event): void;
}
