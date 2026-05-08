<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectSprintInterface;

interface ProjectSprintSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ProjectSprintInterface $sprint): array;
}
