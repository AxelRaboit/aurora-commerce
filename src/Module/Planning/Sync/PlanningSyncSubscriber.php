<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Sync;

use Aurora\Module\Planning\Event\Manager\PlanningEventManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class PlanningSyncSubscriber
{
    public function __construct(
        private PlanningEventManagerInterface $planningEventManager,
    ) {}

    #[AsEventListener]
    public function onScheduled(EntityScheduledEvent $event): void
    {
        $this->planningEventManager->syncFromSource($event);
    }

    #[AsEventListener]
    public function onUnscheduled(EntityUnscheduledEvent $event): void
    {
        $this->planningEventManager->removeBySource($event->sourceType, $event->sourceId);
    }
}
