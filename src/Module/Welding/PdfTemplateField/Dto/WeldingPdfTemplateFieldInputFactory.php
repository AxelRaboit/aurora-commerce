<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplateField\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Welding\Enum\WeldingPdfFieldTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingPdfTemplateFieldInputFactoryInterface::class)]
class WeldingPdfTemplateFieldInputFactory implements WeldingPdfTemplateFieldInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): WeldingPdfTemplateFieldInputInterface
    {
        return new WeldingPdfTemplateFieldInput(
            pdfFieldName: Str::trimFromArray($data, 'pdfFieldName'),
            label: Str::trimFromArray($data, 'label'),
            fieldType: WeldingPdfFieldTypeEnum::tryFrom($data['fieldType'] ?? '') ?? WeldingPdfFieldTypeEnum::Text,
            mappingKey: Str::trimOrNullFromArray($data, 'mappingKey'),
            defaultValue: Str::trimOrNullFromArray($data, 'defaultValue'),
            position: isset($data['position']) ? (int) $data['position'] : 0,
        );
    }
}
