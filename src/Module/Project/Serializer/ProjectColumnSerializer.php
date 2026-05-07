<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectColumn;

final readonly class ProjectColumnSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(ProjectColumn $column): array
    {
        return [
            'id' => $column->getId(),
            'reference' => $column->getReference(),
            'label' => $column->getLabel(),
            'position' => $column->getPosition(),
        ];
    }
}
