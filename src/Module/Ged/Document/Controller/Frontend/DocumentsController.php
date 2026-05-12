<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Controller\Frontend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Ged\Document\Repository\DocumentRepository;
use Aurora\Module\Ged\Document\Serializer\DocumentSerializerInterface;
use Aurora\Module\Ged\DocumentCategory\Repository\DocumentCategoryRepository;
use Aurora\Module\Ged\DocumentCategory\Serializer\DocumentCategorySerializerInterface;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ged', name: 'frontend_ged')]
class DocumentsController extends AbstractController
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentSerializerInterface $documentSerializer,
        private readonly DocumentCategoryRepository $categoryRepository,
        private readonly DocumentCategorySerializerInterface $categorySerializer,
    ) {}

    #[Route('', name: '_index', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        $documents = $this->documentRepository->findPaginated(1, 200, status: DocumentStatusEnum::Published);
        $categories = array_map($this->categorySerializer->serialize(...), $this->categoryRepository->findAllOrdered());

        $byCategory = [];
        foreach ($categories as $category) {
            $byCategory[$category['id']] = ['category' => $category, 'documents' => []];
        }

        $uncategorized = [];

        foreach ($documents['items'] as $document) {
            $serialized = $this->documentSerializer->serialize($document);
            if (null !== $serialized['categoryId'] && isset($byCategory[$serialized['categoryId']])) {
                $byCategory[$serialized['categoryId']]['documents'][] = $serialized;
            } else {
                $uncategorized[] = $serialized;
            }
        }

        $groups = array_values(array_filter($byCategory, static fn (array $group): bool => [] !== $group['documents']));

        return $this->render('@Ged/frontend/documents/index.html.twig', [
            'groups' => $groups,
            'uncategorized' => $uncategorized,
        ]);
    }
}
