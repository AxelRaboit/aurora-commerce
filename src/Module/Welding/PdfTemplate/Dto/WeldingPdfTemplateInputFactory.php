<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Welding\Enum\WeldingPdfTemplateStatusEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingPdfTemplateInputFactoryInterface::class)]
class WeldingPdfTemplateInputFactory implements WeldingPdfTemplateInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): WeldingPdfTemplateInputInterface
    {
        return new WeldingPdfTemplateInput(
            name: Str::trimFromArray($data, 'name'),
            description: Str::trimOrNullFromArray($data, 'description'),
            status: WeldingPdfTemplateStatusEnum::tryFrom($data['status'] ?? '') ?? WeldingPdfTemplateStatusEnum::Draft,
            fileId: isset($data['fileId']) ? (int) $data['fileId'] : null,
            flattenOnGenerate: !empty($data['flattenOnGenerate']),
            requiresSignature: !empty($data['requiresSignature']),
        );
    }
}
