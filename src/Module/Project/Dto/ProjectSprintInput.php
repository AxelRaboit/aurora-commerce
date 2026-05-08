<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProjectSprintInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.projects.errors.sprint_name_required')]
        #[Assert\Length(max: 100)]
        public string $name = '',
        public ?string $startDate = null,
        public ?string $endDate = null,
        public bool $isActive = false,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Str::trimFromArray($data, 'name'),
            startDate: Str::trimOrNullFromArray($data, 'startDate'),
            endDate: Str::trimOrNullFromArray($data, 'endDate'),
            isActive: (bool) ($data['isActive'] ?? false),
        );
    }
}
