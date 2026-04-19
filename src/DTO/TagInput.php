<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TagInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'tags.errors.name_required')]
        #[Assert\Length(max: 255, maxMessage: 'tags.errors.name_too_long')]
        public string $name,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: mb_strtolower(mb_trim((string) ($data['name'] ?? ''))),
        );
    }
}
