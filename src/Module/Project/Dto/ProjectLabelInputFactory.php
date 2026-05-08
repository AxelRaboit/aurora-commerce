<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectLabelInputFactoryInterface::class)]
class ProjectLabelInputFactory implements ProjectLabelInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ProjectLabelInputInterface
    {
        return new ProjectLabelInput(
            name: Str::trimFromArray($data, 'name'),
            color: Str::trimFromArray($data, 'color', 'accent'),
        );
    }
}
