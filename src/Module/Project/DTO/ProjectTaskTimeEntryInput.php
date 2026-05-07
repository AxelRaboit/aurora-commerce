<?php

declare(strict_types=1);

namespace Aurora\Module\Project\DTO;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProjectTaskTimeEntryInput
{
    public function __construct(
        #[Assert\Positive(message: 'backend.projects.errors.time_minutes_invalid')]
        public int $minutes = 0,
        public ?string $note = null,
        public ?string $loggedAt = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            minutes: isset($data['minutes']) && '' !== (string) $data['minutes'] ? (int) $data['minutes'] : 0,
            note: Str::trimOrNullFromArray($data, 'note'),
            loggedAt: Str::trimOrNullFromArray($data, 'loggedAt'),
        );
    }
}
