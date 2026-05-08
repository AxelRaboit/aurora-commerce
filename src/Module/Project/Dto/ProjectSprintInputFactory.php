<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectSprintInputFactoryInterface::class)]
class ProjectSprintInputFactory implements ProjectSprintInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ProjectSprintInputInterface
    {
        return new ProjectSprintInput(
            name: Str::trimFromArray($data, 'name'),
            startDate: Str::trimOrNullFromArray($data, 'startDate'),
            endDate: Str::trimOrNullFromArray($data, 'endDate'),
            isActive: (bool) ($data['isActive'] ?? false),
        );
    }
}
