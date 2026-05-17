<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Serializer;

use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Planning\Event\Entity\PlanningEventInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PlanningEventSerializerInterface::class)]
class PlanningEventSerializer implements PlanningEventSerializerInterface
{
    public function serialize(PlanningEventInterface $event): array
    {
        return [
            'id' => $event->getId(),
            'planningId' => $event->getPlanning()->getId(),
            'title' => $event->getTitle(),
            'description' => $event->getDescription(),
            'location' => $event->getLocation(),
            'startAt' => $event->getStartAt()->format(DATE_ATOM),
            'endAt' => $event->getEndAt()->format(DATE_ATOM),
            'allDay' => $event->isAllDay(),
            'status' => $event->getStatus()->value,
            'sourceType' => $event->getSourceType(),
            'sourceId' => $event->getSourceId(),
            'sourceLabel' => $event->getSourceLabel(),
            'editable' => null === $event->getSourceType(),
            'attendees' => array_map(
                static fn (CoreUserInterface $attendee): array => [
                    'id' => $attendee->getId(),
                    'name' => $attendee->getName(),
                ],
                $event->getAttendees()->toArray(),
            ),
        ];
    }
}
