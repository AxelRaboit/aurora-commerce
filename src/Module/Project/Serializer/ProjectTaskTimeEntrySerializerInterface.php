<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectTaskTimeEntryInterface;

interface ProjectTaskTimeEntrySerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ProjectTaskTimeEntryInterface $entry): array;
}
