<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\DTO;

use Aurora\Core\Support\Str;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DocumentInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.ged.documents.errors.title_required')]
        #[Assert\Length(max: 200)]
        public string $title = '',
        public ?string $description = null,
        public DocumentStatusEnum $status = DocumentStatusEnum::Draft,
        public ?int $categoryId = null,
        public ?int $fileId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: Str::trimFromArray($data, 'title'),
            description: Str::trimOrNullFromArray($data, 'description'),
            status: DocumentStatusEnum::tryFrom($data['status'] ?? '') ?? DocumentStatusEnum::Draft,
            categoryId: isset($data['categoryId']) ? (int) $data['categoryId'] : null,
            fileId: isset($data['fileId']) ? (int) $data['fileId'] : null,
        );
    }
}
