<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Service\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ServiceInput implements ServiceInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.services.errors.name_required')]
        #[Assert\Length(max: 150, maxMessage: 'backend.services.errors.name_too_long')]
        public readonly string $name,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }
}
