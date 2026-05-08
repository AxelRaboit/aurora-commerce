<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectInterface;

interface ProjectSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ProjectInterface $project): array;
}
