<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfDocument\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Welding\PdfDocument\Repository\WeldingPdfDocumentRepository;
use Aurora\Module\Welding\PdfDocument\Serializer\WeldingPdfDocumentSerializerInterface;
use Aurora\Module\Welding\PdfTemplate\Repository\WeldingPdfTemplateRepository;
use Aurora\Module\Welding\PdfTemplate\Serializer\WeldingPdfTemplateSerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class WeldingPdfDocumentsViewBuilder
{
    public function __construct(
        private WeldingPdfDocumentRepository $documentRepository,
        private WeldingPdfDocumentSerializerInterface $documentSerializer,
        private WeldingPdfTemplateRepository $templateRepository,
        private WeldingPdfTemplateSerializerInterface $templateSerializer,
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
            'generatePath' => $this->urlGenerator->generate('backend_welding_pdf_documents_generate'),
            'deletePath' => $this->urlGenerator->generate('backend_welding_pdf_documents_delete', ['id' => '__id__']),
            'listPath' => $this->urlGenerator->generate('backend_welding_pdf_documents_list'),
            'templateListPath' => $this->urlGenerator->generate('backend_welding_pdf_templates_list'),
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
