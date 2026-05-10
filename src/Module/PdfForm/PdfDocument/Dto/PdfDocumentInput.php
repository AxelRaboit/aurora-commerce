<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfDocument\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PdfDocumentInput implements PdfDocumentInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'backend.pdfform.documents.errors.template_required')]
        #[Assert\Positive]
        public readonly int $templateId = 0,
        public readonly ?string $label = null,
        /** @var array<string, string> */
        public readonly array $fieldValues = [],
        public readonly ?string $contextType = null,
        public readonly ?int $contextId = null,
    ) {}

    public function getTemplateId(): int
    {
        return $this->templateId;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getFieldValues(): array
    {
        return $this->fieldValues;
    }

    public function getContextType(): ?string
    {
        return $this->contextType;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }
}
