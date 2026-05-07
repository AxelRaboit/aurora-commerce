<?php

declare(strict_types=1);

namespace Aurora\Module\Project\DTO;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProjectLabelInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.projects.errors.label_name_required')]
        #[Assert\Length(max: 60, maxMessage: 'backend.projects.errors.label_name_too_long')]
        public string $name = '',
        #[Assert\NotBlank]
        #[Assert\Choice(
            choices: ['slate', 'accent', 'rose', 'emerald', 'amber', 'sky', 'violet'],
            message: 'backend.projects.errors.label_color_invalid',
        )]
        public string $color = 'accent',
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Str::trimFromArray($data, 'name'),
            color: Str::trimFromArray($data, 'color', 'accent'),
        );
    }
}
