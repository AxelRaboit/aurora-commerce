<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ProjectTaskTimeEntryInput implements ProjectTaskTimeEntryInputInterface
{
    public function __construct(
        #[Assert\Positive(message: 'backend.projects.errors.time_minutes_invalid')]
        public readonly int $minutes = 0,
        public readonly ?string $note = null,
        public readonly ?string $loggedAt = null,
    ) {}

    public function getMinutes(): int
    {
        return $this->minutes;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getLoggedAt(): ?string
    {
        return $this->loggedAt;
    }
}
