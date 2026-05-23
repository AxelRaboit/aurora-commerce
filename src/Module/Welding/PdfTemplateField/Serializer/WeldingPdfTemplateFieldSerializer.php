<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplateField\Serializer;

use Aurora\Module\Welding\PdfTemplateField\Entity\WeldingPdfTemplateFieldInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(WeldingPdfTemplateFieldSerializerInterface::class)]
class WeldingPdfTemplateFieldSerializer implements WeldingPdfTemplateFieldSerializerInterface
{
    public function __construct(protected readonly TranslatorInterface $translator) {}

    public function serialize(WeldingPdfTemplateFieldInterface $field): array
    {
        return [
            'id' => $field->getId(),
            'pdfFieldName' => $field->getPdfFieldName(),
            'label' => $field->getLabel(),
            'fieldType' => $field->getFieldType()->value,
            'fieldTypeLabel' => $this->translator->trans($field->getFieldType()->getLabelKey()),
            'mappingKey' => $field->getMappingKey(),
            'defaultValue' => $field->getDefaultValue(),
            'position' => $field->getPosition(),
        ];
    }
}
