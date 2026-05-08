<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ProjectColumnInput implements ProjectColumnInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.projects.errors.column_label_required')]
        #[Assert\Length(max: 100, maxMessage: 'backend.projects.errors.column_label_too_long')]
        public readonly string $label = '',
    ) {}

    public function getLabel(): string
    {
        return $this->label;
    }
}
