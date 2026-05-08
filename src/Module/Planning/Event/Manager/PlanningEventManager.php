<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Module\Planning\Event\Entity\PlanningEvent;
use Aurora\Module\Planning\Event\Entity\PlanningEventInterface;
use Aurora\Module\Planning\Event\Repository\PlanningEventRepository;
use Aurora\Module\Planning\Sync\EntityScheduledEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PlanningEventManagerInterface::class)]
class PlanningEventManager implements PlanningEventManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly PlanningEventRepository $eventRepository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function syncFromSource(EntityScheduledEvent $event): PlanningEventInterface
    {
        $existing = $this->eventRepository->findOneBySource($event->sourceType, $event->sourceId);
        $isCreate = !$existing instanceof PlanningEventInterface;
        $planningEvent = $existing ?? $this->createPlanningEvent();

        $this->applyDomainEvent($planningEvent, $event);

        if ($isCreate) {
            $this->entityManager->persist($planningEvent);
        }

        $this->entityManager->flush();

        $isCreate
            ? $this->auditCreated($planningEvent)
            : $this->auditUpdated($planningEvent);

        return $planningEvent;
    }

    public function removeBySource(string $sourceType, int $sourceId): void
    {
        $event = $this->eventRepository->findOneBySource($sourceType, $sourceId);
        if (!$event instanceof PlanningEventInterface) {
            return;
        }

        $this->delete($event);
    }

    public function delete(PlanningEventInterface $event): void
    {
        $this->auditDeleted($event);

        $this->entityManager->remove($event);
        $this->entityManager->flush();
    }

    protected function createPlanningEvent(): PlanningEventInterface
    {
        return new PlanningEvent();
    }

    protected function applyDomainEvent(PlanningEventInterface $planningEvent, EntityScheduledEvent $event): void
    {
        $planningEvent->setPlanning($event->planning);
        $planningEvent->setTitle($event->title);
        $planningEvent->setStartAt($event->startAt);
        $planningEvent->setEndAt($event->endAt);
        $planningEvent->setAllDay($event->allDay);
        $planningEvent->setStatus($event->status);
        $planningEvent->setDescription($event->description);
        $planningEvent->setLocation($event->location);
        $planningEvent->setSourceType($event->sourceType);
        $planningEvent->setSourceId($event->sourceId);
        $planningEvent->setSourceLabel($event->sourceLabel);

        // Reconcile attendees: remove those not in incoming list, add new.
        $desiredIds = [];
        foreach ($event->attendees as $attendee) {
            $desiredIds[(int) $attendee->getId()] = $attendee;
        }

        foreach ($planningEvent->getAttendees()->toArray() as $existing) {
            if (!isset($desiredIds[(int) $existing->getId()])) {
                $planningEvent->removeAttendee($existing);
            }
        }

        foreach ($desiredIds as $attendee) {
            $planningEvent->addAttendee($attendee);
        }
    }

    protected function auditCreated(PlanningEventInterface $event): void
    {
        $this->auditLogger->log('planning', 'planning_event.created', 'PlanningEvent', $event->getId(), $this->auditPayload($event));
    }

    protected function auditUpdated(PlanningEventInterface $event): void
    {
        $this->auditLogger->log('planning', 'planning_event.updated', 'PlanningEvent', $event->getId(), $this->auditPayload($event));
    }

    protected function auditDeleted(PlanningEventInterface $event): void
    {
        $this->auditLogger->log('planning', 'planning_event.deleted', 'PlanningEvent', $event->getId(), $this->auditPayload($event));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(PlanningEventInterface $event): array
    {
        return [
            'title' => $event->getTitle(),
            'planningId' => $event->getPlanning()->getId(),
            'startAt' => $event->getStartAt()->format(DATE_ATOM),
            'endAt' => $event->getEndAt()->format(DATE_ATOM),
            'sourceType' => $event->getSourceType(),
            'sourceId' => $event->getSourceId(),
        ];
    }
}
