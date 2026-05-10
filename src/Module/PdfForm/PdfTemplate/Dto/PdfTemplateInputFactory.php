<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplate\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\PdfForm\Enum\PdfTemplateStatusEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PdfTemplateInputFactoryInterface::class)]
class PdfTemplateInputFactory implements PdfTemplateInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PdfTemplateInputInterface
    {
        return new PdfTemplateInput(
            name: Str::trimFromArray($data, 'name'),
            description: Str::trimOrNullFromArray($data, 'description'),
            status: PdfTemplateStatusEnum::tryFrom($data['status'] ?? '') ?? PdfTemplateStatusEnum::Draft,
            fileId: isset($data['fileId']) ? (int) $data['fileId'] : null,
            flattenOnGenerate: !empty($data['flattenOnGenerate']),
            requiresSignature: !empty($data['requiresSignature']),
        );
    }
}
