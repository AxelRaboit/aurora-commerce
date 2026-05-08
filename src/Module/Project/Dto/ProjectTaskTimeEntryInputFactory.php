<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectTaskTimeEntryInputFactoryInterface::class)]
class ProjectTaskTimeEntryInputFactory implements ProjectTaskTimeEntryInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ProjectTaskTimeEntryInputInterface
    {
        return new ProjectTaskTimeEntryInput(
            minutes: isset($data['minutes']) && '' !== (string) $data['minutes'] ? (int) $data['minutes'] : 0,
            note: Str::trimOrNullFromArray($data, 'note'),
            loggedAt: Str::trimOrNullFromArray($data, 'loggedAt'),
        );
    }
}
