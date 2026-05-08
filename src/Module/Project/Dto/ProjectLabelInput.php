<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ProjectLabelInput implements ProjectLabelInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.projects.errors.label_name_required')]
        #[Assert\Length(max: 60, maxMessage: 'backend.projects.errors.label_name_too_long')]
        public readonly string $name = '',
        #[Assert\NotBlank]
        #[Assert\Choice(
            choices: ['slate', 'accent', 'rose', 'emerald', 'amber', 'sky', 'violet'],
            message: 'backend.projects.errors.label_color_invalid',
        )]
        public readonly string $color = 'accent',
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getColor(): string
    {
        return $this->color;
    }
}
