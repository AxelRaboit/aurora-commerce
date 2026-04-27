<?php

declare(strict_types=1);

namespace Aurora\Core\Media\DTO;

use Aurora\Core\Support\Str;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class MediaFolderInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'media.errors.folder_name_required')]
        #[Assert\Length(max: 150)]
        public string $name,
        public ?int $parentId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: Str::trimOrNull((string) ($data['name'] ?? '')) ?? '',
            parentId: isset($data['parentId']) && (int) $data['parentId'] > 0 ? (int) $data['parentId'] : null,
        );
    }
}
