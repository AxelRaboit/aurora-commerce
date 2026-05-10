<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfDocument\Serializer;

use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocumentInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(PdfDocumentSerializerInterface::class)]
class PdfDocumentSerializer implements PdfDocumentSerializerInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function serialize(PdfDocumentInterface $document): array
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
                ? $this->urlGenerator->generate('backend_pdfform_documents_download', ['id' => $document->getId()])
                : null,
            'createdAt' => $document->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $document->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
