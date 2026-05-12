<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentFolder\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Ged\DocumentFolder\Dto\DocumentFolderInputFactoryInterface;
use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolder;
use Aurora\Module\Ged\DocumentFolder\Manager\DocumentFolderManagerInterface;
use Aurora\Module\Ged\DocumentFolder\Repository\DocumentFolderRepository;
use Aurora\Module\Ged\DocumentFolder\Serializer\DocumentFolderSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/ged/folders', name: 'backend_ged_folders')]
#[IsGranted('ged.folders.manage')]
final class DocumentFoldersController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly DocumentFolderSerializerInterface $serializer,
        private readonly DocumentFolderManagerInterface $manager,
        private readonly PayloadValidator $payloadValidator,
        private readonly DocumentFolderRepository $folderRepository,
        private readonly DocumentFolderInputFactoryInterface $inputFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        $folders = array_map($this->serializer->serialize(...), $this->folderRepository->findAllOrdered());

        return $this->render('@Ged/backend/folders/index.html.twig', [
            'folders' => $folders,
            'createPath' => $this->urlGenerator->generate('backend_ged_folders_create'),
            'updatePath' => $this->urlGenerator->generate('backend_ged_folders_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_ged_folders_delete', ['id' => '__id__']),
            'movePath' => $this->urlGenerator->generate('backend_ged_folders_move', ['id' => '__id__']),
            'reorderPath' => $this->urlGenerator->generate('backend_ged_folders_reorder'),
        ]);
    }

    #[Route('/create', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $folder = $this->manager->create($input);

        return $this->jsonSuccess(['folder' => $this->serializer->serialize($folder), 'folders' => $this->allFolders()]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    public function update(DocumentFolder $folder, Request $request): JsonResponse
    {
        $input = $this->inputFactory->fromArray($this->decodeJson($request));
        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->manager->update($folder, $input);

        return $this->jsonSuccess(['folder' => $this->serializer->serialize($folder), 'folders' => $this->allFolders()]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(DocumentFolder $folder): JsonResponse
    {
        $this->manager->delete($folder);

        return $this->jsonSuccess(['folders' => $this->allFolders()]);
    }

    #[Route('/{id}/move', name: '_move', methods: [HttpMethodEnum::Post->value])]
    public function move(DocumentFolder $folder, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $parentId = $data['parentId'] ?? null;
        $newParent = null !== $parentId ? $this->folderRepository->find((int) $parentId) : null;

        $this->manager->move($folder, $newParent);

        return $this->jsonSuccess(['folders' => $this->allFolders()]);
    }

    #[Route('/reorder', name: '_reorder', methods: [HttpMethodEnum::Post->value])]
    public function reorder(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $orderedIds = array_map(intval(...), $data['ids'] ?? []);

        $this->manager->reorder($orderedIds);

        return $this->jsonSuccess(['folders' => $this->allFolders()]);
    }

    private function allFolders(): array
    {
        return array_map($this->serializer->serialize(...), $this->folderRepository->findAllOrdered());
    }
}
