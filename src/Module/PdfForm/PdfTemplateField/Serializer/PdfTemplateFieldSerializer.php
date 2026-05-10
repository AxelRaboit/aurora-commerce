<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplateField\Serializer;

use Aurora\Module\PdfForm\PdfTemplateField\Entity\PdfTemplateFieldInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(PdfTemplateFieldSerializerInterface::class)]
class PdfTemplateFieldSerializer implements PdfTemplateFieldSerializerInterface
{
    public function __construct(protected readonly TranslatorInterface $translator) {}

    public function serialize(PdfTemplateFieldInterface $field): array
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
