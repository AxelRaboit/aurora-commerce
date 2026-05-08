<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectColumnInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectColumnSerializerInterface::class)]
class ProjectColumnSerializer implements ProjectColumnSerializerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(ProjectColumnInterface $column): array
    {
        return [
            'id' => $column->getId(),
            'reference' => $column->getReference(),
            'label' => $column->getLabel(),
            'position' => $column->getPosition(),
        ];
    }
}
