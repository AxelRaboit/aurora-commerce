<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplate\Dto;

use Aurora\Module\PdfForm\Enum\PdfTemplateStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

class PdfTemplateInput implements PdfTemplateInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.pdfform.templates.errors.name_required')]
        #[Assert\Length(max: 200)]
        public readonly string $name = '',
        public readonly ?string $description = null,
        public readonly PdfTemplateStatusEnum $status = PdfTemplateStatusEnum::Draft,
        public readonly ?int $fileId = null,
        public readonly bool $flattenOnGenerate = false,
        public readonly bool $requiresSignature = false,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): PdfTemplateStatusEnum
    {
        return $this->status;
    }

    public function getFileId(): ?int
    {
        return $this->fileId;
    }

    public function isFlattenOnGenerate(): bool
    {
        return $this->flattenOnGenerate;
    }

    public function isRequiresSignature(): bool
    {
        return $this->requiresSignature;
    }
}
