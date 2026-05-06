<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\View;

use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Ged\Document\Repository\DocumentRepository;
use Aurora\Module\Ged\Document\Serializer\DocumentSerializer;
use Aurora\Module\Ged\DocumentCategory\Repository\DocumentCategoryRepository;
use Aurora\Module\Ged\DocumentCategory\Serializer\DocumentCategorySerializer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class DocumentsViewBuilder
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentSerializer $documentSerializer,
        private DocumentCategoryRepository $categoryRepository,
        private DocumentCategorySerializer $categorySerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function indexView(PaginationRequest $pagination): array
    {
        $categories = array_map(
            $this->categorySerializer->serialize(...),
            $this->categoryRepository->findAllOrdered(),
        );

        return [
            'documents' => $this->buildListPayload($pagination),
            'categories' => $categories,
            'search' => $pagination->search ?? '',
            'createPath' => $this->urlGenerator->generate('backend_ged_documents_create'),
            'updatePath' => $this->urlGenerator->generate('backend_ged_documents_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_ged_documents_delete', ['id' => '__id__']),
            'listPath' => $this->urlGenerator->generate('backend_ged_documents_list'),
            'mediaPickerPath' => $this->urlGenerator->generate('backend_media_list'),
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
