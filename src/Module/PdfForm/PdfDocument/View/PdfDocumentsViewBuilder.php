<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfDocument\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\PdfForm\PdfDocument\Repository\PdfDocumentRepository;
use Aurora\Module\PdfForm\PdfDocument\Serializer\PdfDocumentSerializerInterface;
use Aurora\Module\PdfForm\PdfTemplate\Repository\PdfTemplateRepository;
use Aurora\Module\PdfForm\PdfTemplate\Serializer\PdfTemplateSerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class PdfDocumentsViewBuilder
{
    public function __construct(
        private PdfDocumentRepository $documentRepository,
        private PdfDocumentSerializerInterface $documentSerializer,
        private PdfTemplateRepository $templateRepository,
        private PdfTemplateSerializerInterface $templateSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function indexView(PaginationRequest $pagination): array
    {
        $activeTemplates = array_map(
            $this->templateSerializer->serialize(...),
            $this->templateRepository->findActive(),
        );

        return [
            'documents' => $this->buildListPayload($pagination),
            'templates' => $activeTemplates,
            'search' => $pagination->search ?? '',
            'generatePath' => $this->urlGenerator->generate('backend_pdfform_documents_generate'),
            'deletePath' => $this->urlGenerator->generate('backend_pdfform_documents_delete', ['id' => '__id__']),
            'listPath' => $this->urlGenerator->generate('backend_pdfform_documents_list'),
            'templateListPath' => $this->urlGenerator->generate('backend_pdfform_templates_list'),
        ];
    }

    public function buildListPayload(PaginationRequest $pagination): array
    {
        $result = $this->documentRepository->findPaginated($pagination->page, search: $pagination->search);

        return [
            'success' => true,
            'items' => array_map($this->documentSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
