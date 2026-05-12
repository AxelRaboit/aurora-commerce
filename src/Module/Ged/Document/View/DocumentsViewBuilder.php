<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Ged\Document\Repository\DocumentRepository;
use Aurora\Module\Ged\Document\Serializer\DocumentSerializerInterface;
use Aurora\Module\Ged\DocumentCategory\Repository\DocumentCategoryRepository;
use Aurora\Module\Ged\DocumentCategory\Serializer\DocumentCategorySerializerInterface;
use Aurora\Module\Ged\DocumentFolder\Repository\DocumentFolderRepository;
use Aurora\Module\Ged\DocumentFolder\Serializer\DocumentFolderSerializerInterface;
use Aurora\Module\Ged\DocumentTag\Repository\DocumentTagRepository;
use Aurora\Module\Ged\DocumentTag\Serializer\DocumentTagSerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class DocumentsViewBuilder
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentSerializerInterface $documentSerializer,
        private DocumentCategoryRepository $categoryRepository,
        private DocumentCategorySerializerInterface $categorySerializer,
        private DocumentTagRepository $tagRepository,
        private DocumentTagSerializerInterface $tagSerializer,
        private DocumentFolderRepository $folderRepository,
        private DocumentFolderSerializerInterface $folderSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function indexView(PaginationRequest $pagination): array
    {
        $categories = array_map(
            $this->categorySerializer->serialize(...),
            $this->categoryRepository->findAllOrdered(),
        );

        $tags = array_map(
            $this->tagSerializer->serialize(...),
            $this->tagRepository->findAllOrdered(),
        );

        $folders = array_map(
            $this->folderSerializer->serialize(...),
            $this->folderRepository->findAllOrdered(),
        );

        return [
            'documents' => $this->buildListPayload($pagination),
            'categories' => $categories,
            'tags' => $tags,
            'folders' => $folders,
            'search' => $pagination->search ?? '',
            'createPath' => $this->urlGenerator->generate('backend_ged_documents_create'),
            'updatePath' => $this->urlGenerator->generate('backend_ged_documents_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_ged_documents_delete', ['id' => '__id__']),
            'listPath' => $this->urlGenerator->generate('backend_ged_documents_list'),
            'mediaPickerPath' => $this->urlGenerator->generate('backend_media_list'),
        ];
    }

    public function buildListPayload(
        PaginationRequest $pagination,
        ?int $categoryId = null,
        ?int $tagId = null,
        ?int $folderId = null,
    ): array {
        $result = $this->documentRepository->findPaginated(
            $pagination->page,
            search: $pagination->search,
            categoryId: $categoryId,
            tagId: $tagId,
            folderId: $folderId,
        );

        return [
            'success' => true,
            'items' => array_map($this->documentSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
