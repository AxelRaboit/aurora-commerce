<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Ged\Document\Dto\DocumentInputFactoryInterface;
use Aurora\Module\Ged\Document\Entity\Document;
use Aurora\Module\Ged\Document\Manager\DocumentManagerInterface;
use Aurora\Module\Ged\Document\Repository\DocumentVersionRepository;
use Aurora\Module\Ged\Document\Serializer\DocumentSerializerInterface;
use Aurora\Module\Ged\Document\Serializer\DocumentVersionSerializer;
use Aurora\Module\Ged\Document\View\DocumentsViewBuilder;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/ged/documents', name: 'backend_ged_documents')]
#[IsGranted('ged.documents.view')]
final class DocumentsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly DocumentSerializerInterface $serializer,
        private readonly DocumentManagerInterface $manager,
        private readonly PayloadValidator $payloadValidator,
        private readonly DocumentsViewBuilder $viewBuilder,
        private readonly DocumentInputFactoryInterface $inputFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly DocumentVersionRepository $versionRepository,
        private readonly DocumentVersionSerializer $versionSerializer,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination): Response
    {
        return $this->render('@Ged/backend/documents/index.html.twig', $this->viewBuilder->indexView($pagination));
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(Request $request, PaginationRequest $pagination): JsonResponse
    {
        $categoryId = $request->query->getInt('categoryId') ?: null;
        $tagId = $request->query->getInt('tagId') ?: null;
        $folderId = $request->query->getInt('folderId') ?: null;
        $statusValue = $request->query->getString('status');
        $status = '' !== $statusValue ? DocumentStatusEnum::tryFrom($statusValue) : null;

        return $this->json($this->viewBuilder->buildListPayload($pagination, $categoryId, $tagId, $folderId, $status));
    }

    #[Route('/{id}', name: '_show', methods: [HttpMethodEnum::Get->value])]
    public function show(Document $document): Response
    {
        return $this->render('@Ged/backend/documents/show.html.twig', [
            'document' => $this->serializer->serialize($document),
            'backPath' => $this->urlGenerator->generate('backend_ged_documents'),
            'updatePath' => $this->urlGenerator->generate('backend_ged_documents_update', ['id' => $document->getId()]),
            'deletePath' => $this->urlGenerator->generate('backend_ged_documents_delete', ['id' => $document->getId()]),
            'listPath' => $this->urlGenerator->generate('backend_ged_documents'),
        ]);
    }

    #[Route('/{id}/versions', name: '_versions', methods: [HttpMethodEnum::Get->value])]
    public function versions(Document $document): JsonResponse
    {
        $versions = $this->versionRepository->findByDocument($document);

        return $this->json([
            'success' => true,
            'versions' => array_map($this->versionSerializer->serialize(...), $versions),
        ]);
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('ged.documents.create')]
    public function create(Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $document = $this->manager->create($input);

        return $this->jsonSuccess(['document' => $this->serializer->serialize($document)]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('ged.documents.edit')]
    public function update(Document $document, Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->manager->update($document, $input);

        return $this->jsonSuccess(['document' => $this->serializer->serialize($document)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('ged.documents.delete')]
    public function delete(Document $document): JsonResponse
    {
        $this->manager->delete($document);

        return $this->jsonSuccess();
    }
}
