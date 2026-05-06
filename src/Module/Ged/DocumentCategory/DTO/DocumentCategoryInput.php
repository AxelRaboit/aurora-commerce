<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentCategory\DTO;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DocumentCategoryInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.ged.categories.errors.name_required')]
        #[Assert\Length(max: 150)]
        public string $name = '',
        public ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: Str::trimFromArray($data, 'name'),
            description: Str::trimOrNullFromArray($data, 'description'),
        );
    }
}
