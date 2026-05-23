<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Welding\Enum\WeldingPdfTemplateStatusEnum;
use Aurora\Module\Welding\PdfTemplate\Repository\WeldingPdfTemplateRepository;
use Aurora\Module\Welding\PdfTemplate\Serializer\WeldingPdfTemplateSerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class WeldingPdfTemplatesViewBuilder
{
    public function __construct(
        private WeldingPdfTemplateRepository $templateRepository,
        private WeldingPdfTemplateSerializerInterface $templateSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function indexView(PaginationRequest $pagination): array
    {
        return [
            'templates' => $this->buildListPayload($pagination),
            'search' => $pagination->search ?? '',
            'createPath' => $this->urlGenerator->generate('backend_welding_pdf_templates_create'),
            'updatePath' => $this->urlGenerator->generate('backend_welding_pdf_templates_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_welding_pdf_templates_delete', ['id' => '__id__']),
            'detectFieldsPath' => $this->urlGenerator->generate('backend_welding_pdf_templates_detect_fields', ['id' => '__id__']),
            'updateFieldPath' => $this->urlGenerator->generate('backend_welding_pdf_template_fields_update', ['id' => '__id__']),
            'listPath' => $this->urlGenerator->generate('backend_welding_pdf_templates_list'),
            'mediaPickerPath' => $this->urlGenerator->generate('backend_media_list'),
        ];
    }

    public function buildListPayload(PaginationRequest $pagination, ?string $statusFilter = null): array
    {
        $status = null !== $statusFilter ? WeldingPdfTemplateStatusEnum::tryFrom($statusFilter) : null;
        $result = $this->templateRepository->findPaginated($pagination->page, search: $pagination->search, status: $status);

        return [
            'success' => true,
            'items' => array_map($this->templateSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
