<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProjectColumnInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.projects.errors.column_label_required')]
        #[Assert\Length(max: 100, maxMessage: 'backend.projects.errors.column_label_too_long')]
        public string $label = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            label: Str::trimFromArray($data, 'label'),
        );
    }
}
