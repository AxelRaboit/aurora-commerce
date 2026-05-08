<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class AgencyInput implements AgencyInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.agencies.errors.name_required')]
        #[Assert\Length(max: 150, maxMessage: 'backend.agencies.errors.name_too_long')]
        public string $name,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }
}
