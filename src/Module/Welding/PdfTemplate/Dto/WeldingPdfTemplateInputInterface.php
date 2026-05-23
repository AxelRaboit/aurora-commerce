<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\Dto;

use Aurora\Module\Welding\Enum\WeldingPdfTemplateStatusEnum;

interface WeldingPdfTemplateInputInterface
{
    public function getName(): string;

    public function getDescription(): ?string;

    public function getStatus(): WeldingPdfTemplateStatusEnum;

    public function getFileId(): ?int;

    public function isFlattenOnGenerate(): bool;

    public function isRequiresSignature(): bool;
}
