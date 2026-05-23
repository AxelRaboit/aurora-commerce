<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\Serializer;

use Aurora\Module\Media\Library\Service\MediaUrlGenerator;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;
use Aurora\Module\Welding\PdfTemplateField\Serializer\WeldingPdfTemplateFieldSerializerInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(WeldingPdfTemplateSerializerInterface::class)]
class WeldingPdfTemplateSerializer implements WeldingPdfTemplateSerializerInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly WeldingPdfTemplateFieldSerializerInterface $fieldSerializer,
        protected readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {}

    public function serialize(WeldingPdfTemplateInterface $template): array
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
            'fileUrl' => $this->mediaUrlGenerator->publicUrl($file),
            'flattenOnGenerate' => $template->isFlattenOnGenerate(),
            'requiresSignature' => $template->isRequiresSignature(),
            'fieldCount' => $template->getFields()->count(),
            'fields' => array_map($this->fieldSerializer->serialize(...), $template->getFields()->toArray()),
            'createdAt' => $template->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $template->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
