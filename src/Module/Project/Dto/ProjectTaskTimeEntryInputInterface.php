<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

interface ProjectTaskTimeEntryInputInterface
{
    public function getMinutes(): int;

    public function getNote(): ?string;

    public function getLoggedAt(): ?string;
}
