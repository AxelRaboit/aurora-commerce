<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Event\Dto;

use Aurora\Module\Planning\Event\Enum\PlanningEventStatusEnum;

interface PlanningEventInputInterface
{
    public function getPlanningId(): int;

    public function getTitle(): string;

    public function getDescription(): ?string;

    public function getLocation(): ?string;

    public function getStartAt(): string;

    public function getEndAt(): string;

    public function isAllDay(): bool;

    public function getStatus(): string;

    public function getStatusEnum(): PlanningEventStatusEnum;

    /** @return list<int> */
    public function getAttendeeIds(): array;
}
