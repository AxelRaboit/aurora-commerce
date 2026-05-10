<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplateField\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\PdfForm\Enum\PdfFieldTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PdfTemplateFieldInputFactoryInterface::class)]
class PdfTemplateFieldInputFactory implements PdfTemplateFieldInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): PdfTemplateFieldInputInterface
    {
        return new PdfTemplateFieldInput(
            pdfFieldName: Str::trimFromArray($data, 'pdfFieldName'),
            label: Str::trimFromArray($data, 'label'),
            fieldType: PdfFieldTypeEnum::tryFrom($data['fieldType'] ?? '') ?? PdfFieldTypeEnum::Text,
            mappingKey: Str::trimOrNullFromArray($data, 'mappingKey'),
            defaultValue: Str::trimOrNullFromArray($data, 'defaultValue'),
            position: isset($data['position']) ? (int) $data['position'] : 0,
        );
    }
}
