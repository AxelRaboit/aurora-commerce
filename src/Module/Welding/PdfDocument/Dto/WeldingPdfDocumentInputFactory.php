<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfDocument\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(WeldingPdfDocumentInputFactoryInterface::class)]
class WeldingPdfDocumentInputFactory implements WeldingPdfDocumentInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): WeldingPdfDocumentInputInterface
    {
        $fieldValues = [];
        if (isset($data['fieldValues']) && is_array($data['fieldValues'])) {
            foreach ($data['fieldValues'] as $key => $value) {
                $fieldValues[(string) $key] = (string) $value;
            }
        }

        return new WeldingPdfDocumentInput(
            templateId: isset($data['templateId']) ? (int) $data['templateId'] : 0,
            label: Str::trimOrNullFromArray($data, 'label'),
            fieldValues: $fieldValues,
            contextType: Str::trimOrNullFromArray($data, 'contextType'),
            contextId: isset($data['contextId']) ? (int) $data['contextId'] : null,
        );
    }
}
