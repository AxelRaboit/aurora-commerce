<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Entity;

use Aurora\Core\Timestampable\TimestampableInterface;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Planning\Event\Enum\PlanningEventStatusEnum;
use Aurora\Module\Planning\Planning\Entity\PlanningInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;

interface PlanningEventInterface extends TimestampableInterface
{
    public function getId(): ?int;

    public function getPlanning(): PlanningInterface;

    public function setPlanning(PlanningInterface $planning): static;

    public function getTitle(): string;

    public function setTitle(string $title): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getLocation(): ?string;

    public function setLocation(?string $location): static;

    public function getStartAt(): DateTimeImmutable;

    public function setStartAt(DateTimeImmutable $startAt): static;

    public function getEndAt(): DateTimeImmutable;

    public function setEndAt(DateTimeImmutable $endAt): static;

    public function isAllDay(): bool;

    public function setAllDay(bool $allDay): static;

    public function getStatus(): PlanningEventStatusEnum;

    public function setStatus(PlanningEventStatusEnum $status): static;

    public function getSourceType(): ?string;

    public function setSourceType(?string $sourceType): static;

    public function getSourceId(): ?int;

    public function setSourceId(?int $sourceId): static;

    public function getSourceLabel(): ?string;

    public function setSourceLabel(?string $sourceLabel): static;

    /** @return Collection<int, CoreUserInterface> */
    public function getAttendees(): Collection;

    public function addAttendee(CoreUserInterface $attendee): static;

    public function removeAttendee(CoreUserInterface $attendee): static;
}
