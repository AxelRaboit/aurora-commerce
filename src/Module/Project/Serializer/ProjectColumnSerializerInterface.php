<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectColumnInterface;

interface ProjectColumnSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ProjectColumnInterface $column): array;
}
