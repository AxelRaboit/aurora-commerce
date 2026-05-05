<?php

declare(strict_types=1);

namespace Aurora\Core\Service\DTO;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ServiceInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'admin.services.errors.name_required')]
        #[Assert\Length(max: 150, maxMessage: 'admin.services.errors.name_too_long')]
        public string $name,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(name: Str::trimFromArray($data, 'name'));
    }
}
