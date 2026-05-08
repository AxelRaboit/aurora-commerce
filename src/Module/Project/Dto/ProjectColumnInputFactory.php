<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectColumnInputFactoryInterface::class)]
class ProjectColumnInputFactory implements ProjectColumnInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ProjectColumnInputInterface
    {
        return new ProjectColumnInput(label: Str::trimFromArray($data, 'label'));
    }
}
