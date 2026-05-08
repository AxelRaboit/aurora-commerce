<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Dto;

use Aurora\Module\Planning\Event\Enum\PlanningEventStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

class PlanningEventInput implements PlanningEventInputInterface
{
    public function __construct(
        #[Assert\Positive(message: 'backend.planning_events.errors.planning_required')]
        public readonly int $planningId = 0,
        #[Assert\NotBlank(message: 'backend.planning_events.errors.title_required')]
        #[Assert\Length(max: 255)]
        public readonly string $title = '',
        public readonly ?string $description = null,
        public readonly ?string $location = null,
        #[Assert\NotBlank(message: 'backend.planning_events.errors.start_required')]
        public readonly string $startAt = '',
        #[Assert\NotBlank(message: 'backend.planning_events.errors.end_required')]
        public readonly string $endAt = '',
        public readonly bool $allDay = false,
        #[Assert\Choice(callback: [PlanningEventStatusEnum::class, 'values'], message: 'backend.planning_events.errors.status_invalid')]
        public readonly string $status = PlanningEventStatusEnum::Confirmed->value,
        /** @var list<int> */
        #[Assert\All([new Assert\Positive()])]
        public readonly array $attendeeIds = [],
    ) {}

    public function getPlanningId(): int
    {
        return $this->planningId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getStartAt(): string
    {
        return $this->startAt;
    }

    public function getEndAt(): string
    {
        return $this->endAt;
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusEnum(): PlanningEventStatusEnum
    {
        return PlanningEventStatusEnum::from($this->status);
    }

    public function getAttendeeIds(): array
    {
        return $this->attendeeIds;
    }
}
