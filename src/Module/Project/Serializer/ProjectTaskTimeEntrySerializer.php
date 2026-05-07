<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectTaskTimeEntry;

final readonly class ProjectTaskTimeEntrySerializer
{
    /** @return array<string, mixed> */
    public function serialize(ProjectTaskTimeEntry $entry): array
    {
        return [
            'id' => $entry->getId(),
            'minutes' => $entry->getMinutes(),
            'note' => $entry->getNote(),
            'loggedAt' => $entry->getLoggedAt()->format('Y-m-d'),
            'userId' => $entry->getUser()->getId(),
            'userName' => $entry->getUser()->getName(),
        ];
    }
}
