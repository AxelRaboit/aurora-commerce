<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

interface ProjectLabelInputInterface
{
    public function getName(): string;

    public function getColor(): string;
}
