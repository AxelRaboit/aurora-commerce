<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ProjectSprintInput implements ProjectSprintInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.projects.errors.sprint_name_required')]
        #[Assert\Length(max: 100)]
        public readonly string $name = '',
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly bool $isActive = false,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
