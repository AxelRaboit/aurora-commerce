<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplate\Dto;

use Aurora\Module\PdfForm\Enum\PdfTemplateStatusEnum;

interface PdfTemplateInputInterface
{
    public function getName(): string;

    public function getDescription(): ?string;

    public function getStatus(): PdfTemplateStatusEnum;

    public function getFileId(): ?int;

    public function isFlattenOnGenerate(): bool;

    public function isRequiresSignature(): bool;
}
