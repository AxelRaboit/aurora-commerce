<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfDocument\Serializer;

use Aurora\Module\Welding\PdfDocument\Entity\WeldingPdfDocumentInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(WeldingPdfDocumentSerializerInterface::class)]
class WeldingPdfDocumentSerializer implements WeldingPdfDocumentSerializerInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function serialize(WeldingPdfDocumentInterface $document): array
    {
        $template = $document->getTemplate();
        $hasFile = null !== $document->getFilePath();

        return [
            'id' => $document->getId(),
            'reference' => $document->getReference(),
            'label' => $document->getLabel(),
            'status' => $document->getStatus()->value,
            'statusLabel' => $this->translator->trans($document->getStatus()->getLabelKey()),
            'templateId' => $template?->getId(),
            'templateName' => $template?->getName(),
            'fieldValues' => $document->getFieldValues(),
            'contextType' => $document->getContextType(),
            'contextId' => $document->getContextId(),
            'downloadUrl' => $hasFile
                ? $this->urlGenerator->generate('backend_welding_pdf_documents_download', ['id' => $document->getId()])
                : null,
            'createdAt' => $document->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $document->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
