<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\Dto;

use Aurora\Module\Welding\Enum\WeldingPdfTemplateStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

class WeldingPdfTemplateInput implements WeldingPdfTemplateInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.welding.pdf_templates.errors.name_required')]
        #[Assert\Length(max: 200)]
        public readonly string $name = '',
        public readonly ?string $description = null,
        public readonly WeldingPdfTemplateStatusEnum $status = WeldingPdfTemplateStatusEnum::Draft,
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

    public function getStatus(): WeldingPdfTemplateStatusEnum
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
