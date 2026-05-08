<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

interface ProjectSprintInputInterface
{
    public function getName(): string;

    public function getStartDate(): ?string;

    public function getEndDate(): ?string;

    public function isActive(): bool;
}
