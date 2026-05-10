<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplate\Serializer;

use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;
use Aurora\Module\PdfForm\PdfTemplateField\Serializer\PdfTemplateFieldSerializerInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(PdfTemplateSerializerInterface::class)]
class PdfTemplateSerializer implements PdfTemplateSerializerInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly PdfTemplateFieldSerializerInterface $fieldSerializer,
    ) {}

    public function serialize(PdfTemplateInterface $template): array
    {
        $file = $template->getFile();

        return [
            'id' => $template->getId(),
            'name' => $template->getName(),
            'description' => $template->getDescription(),
            'status' => $template->getStatus()->value,
            'statusLabel' => $this->translator->trans($template->getStatus()->getLabelKey()),
            'fileId' => $file?->getId(),
            'fileName' => $file?->getOriginalName(),
            'fileUrl' => $file?->getPublicUrl(),
            'flattenOnGenerate' => $template->isFlattenOnGenerate(),
            'requiresSignature' => $template->isRequiresSignature(),
            'fieldCount' => $template->getFields()->count(),
            'fields' => array_map($this->fieldSerializer->serialize(...), $template->getFields()->toArray()),
            'createdAt' => $template->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $template->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
