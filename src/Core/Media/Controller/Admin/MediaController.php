<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Controller\Admin;

use Aurora\Core\Audit\Repository\AuditLogRepository;
use Aurora\Core\Audit\Serializer\AuditLogSerializer;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Media\Contract\MediaManagerInterface;
use Aurora\Core\Media\DTO\MediaFolderInput;
use Aurora\Core\Media\DTO\MediaInput;
use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Media\Entity\MediaFolder;
use Aurora\Core\Media\Enum\MimeTypeEnum;
use Aurora\Core\Media\Repository\MediaFolderRepository;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Media\Serializer\MediaFolderSerializer;
use Aurora\Core\Media\Serializer\MediaSerializer;
use Aurora\Core\Media\Service\MediaUsageService;
use Aurora\Core\Media\View\MediaViewBuilder;
use Aurora\Core\Validation\Service\PayloadValidator;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/media', name: 'backend_media')]
#[IsGranted('core.media.manage')]
class MediaController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly MediaFolderRepository $folderRepository,
        private readonly MediaManagerInterface $mediaManager,
        private readonly MediaSerializer $mediaSerializer,
        private readonly MediaFolderSerializer $folderSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly AuditLogSerializer $auditLogSerializer,
        private readonly MediaUsageService $mediaUsageService,
        private readonly MediaViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        $folderId = $request->query->getInt('folderId') ?: null;
        $search = mb_trim((string) $request->query->get('search', ''));

        return $this->render('@Core/admin/media/index.html.twig', $this->viewBuilder->indexView($folderId, $search));
    }

    #[Route('/{id}/info', name: '_info', methods: [HttpMethodEnum::Get->value])]
    public function info(Media $media): JsonResponse
    {
        return $this->json(['media' => $this->mediaSerializer->serialize($media)]);
    }

    #[Route('/{id}/usage', name: '_usage', methods: [HttpMethodEnum::Get->value])]
    public function usage(Media $media): JsonResponse
    {
        $counts = $this->mediaRepository->countUsages((int) $media->getId());
        $detailed = $this->mediaUsageService->findUsages((int) $media->getId());

        return $this->json([
            // Legacy keys preserved for back-compat with existing UI panels.
            'directCount' => $counts['directCount'],
            'contentCount' => $counts['contentCount'],
            'total' => $detailed['total'],
            // New rich payload used by the delete confirmation modal.
            'groups' => $detailed['groups'],
        ]);
    }

    #[Route('/{id}/history', name: '_history', methods: [HttpMethodEnum::Get->value])]
    public function history(Media $media, Request $request): JsonResponse
    {
        $result = $this->auditLogRepository->findPaginatedForEntity(
            'Media',
            (int) $media->getId(),
            $request->query->getInt('page', 1),
            10,
        );

        return $this->json([
            'items' => array_map($this->auditLogSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(Request $request): JsonResponse
    {
        $search = mb_trim((string) $request->query->get('search', ''));
        $folderId = $request->query->getInt('folderId') ?: null;
        $folder = null !== $folderId ? $this->folderRepository->find($folderId) : null;
        $allFolders = $request->query->getBoolean('all');

        $items = array_map(
            $this->mediaSerializer->serialize(...),
            $allFolders && null === $folder
                ? $this->mediaRepository->findAcrossFolders($search ?: null)
                : $this->mediaRepository->findByFolder($folder, $search ?: null),
        );

        $serializer = $this->folderSerializer->withMediaCounts($this->mediaRepository->countGroupedByFolders());

        $folders = array_map(
            $serializer->serialize(...),
            $this->folderRepository->findAllOrdered(),
        );

        return $this->json(['items' => $items, 'folders' => $folders]);
    }

    #[Route('/upload', name: '_upload', methods: [HttpMethodEnum::Post->value])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('image');
        if (!$file) {
            return $this->json(['success' => 0, 'message' => 'No file provided.'], Response::HTTP_BAD_REQUEST);
        }

        if (null === MimeTypeEnum::tryFrom($file->getMimeType() ?? '')) {
            return $this->json(['success' => 0, 'message' => 'Invalid file type.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $folder = null;
        $folderId = $request->request->getInt('folderId') ?: null;
        if (null !== $folderId) {
            $folder = $this->folderRepository->find($folderId);
        }

        $media = $this->mediaManager->upload($file, $folder);

        return $this->json([
            'success' => 1,
            'file' => [
                'id' => $media->getId(),
                'url' => $media->getPublicUrl(),
                'width' => $media->getWidth(),
                'height' => $media->getHeight(),
            ],
            'media' => $this->mediaSerializer->serialize($media),
        ]);
    }

    #[Route('/{id}/edit', name: '_edit', methods: [HttpMethodEnum::Post->value])]
    public function edit(Media $media, Request $request): JsonResponse
    {
        $input = MediaInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        try {
            $this->mediaManager->update($media, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['folderId' => $invalidArgumentException->getMessage()], Response::HTTP_OK);
        }

        return $this->jsonSuccess(['media' => $this->mediaSerializer->serialize($media)]);
    }

    #[Route('/{id}/move', name: '_move', methods: [HttpMethodEnum::Post->value])]
    public function move(Media $media, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $folderId = isset($data['folderId']) && (int) $data['folderId'] > 0 ? (int) $data['folderId'] : null;
        $folder = null !== $folderId ? $this->folderRepository->find($folderId) : null;

        $this->mediaManager->move($media, $folder);

        return $this->jsonSuccess(['media' => $this->mediaSerializer->serialize($media)]);
    }

    #[Route('/reorder', name: '_reorder', methods: [HttpMethodEnum::Post->value])]
    public function reorder(Request $request): JsonResponse
    {
        $ids = $this->decodeJson($request)['ids'] ?? [];
        $this->mediaManager->reorder($ids);

        return $this->jsonSuccess();
    }

    #[Route('/bulk-delete', name: '_bulk_delete', methods: [HttpMethodEnum::Post->value])]
    public function bulkDelete(Request $request): JsonResponse
    {
        $ids = $this->decodeJson($request)['ids'] ?? [];
        $this->mediaManager->bulkDelete($ids);

        return $this->jsonSuccess();
    }

    #[Route('/bulk-move', name: '_bulk_move', methods: [HttpMethodEnum::Post->value])]
    public function bulkMove(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $ids = $data['ids'] ?? [];
        $folderId = $data['folderId'] ?? null;
        $folder = null !== $folderId ? $this->folderRepository->find($folderId) : null;
        $this->mediaManager->bulkMove($ids, $folder);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Media $media): JsonResponse
    {
        $this->mediaManager->delete($media);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/crop', name: '_crop', methods: [HttpMethodEnum::Post->value])]
    public function crop(Media $media, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $this->mediaManager->crop(
            $media,
            (int) ($data['x'] ?? 0),
            (int) ($data['y'] ?? 0),
            (int) ($data['width'] ?? 1),
            (int) ($data['height'] ?? 1),
        );

        return $this->jsonSuccess(['media' => $this->mediaSerializer->serialize($media)]);
    }

    #[Route('/folders', name: '_folder_create', methods: [HttpMethodEnum::Post->value])]
    public function createFolder(Request $request): JsonResponse
    {
        $input = MediaFolderInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        try {
            $folder = $this->mediaManager->createFolder($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['parentId' => $invalidArgumentException->getMessage()], Response::HTTP_OK);
        }

        return $this->jsonSuccess(['folder' => $this->folderSerializer->serialize($folder)]);
    }

    #[Route('/folders/{id}/edit', name: '_folder_edit', methods: [HttpMethodEnum::Post->value])]
    public function editFolder(MediaFolder $folder, Request $request): JsonResponse
    {
        $input = MediaFolderInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors, Response::HTTP_OK);
        }

        try {
            $this->mediaManager->updateFolder($folder, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['parentId' => $invalidArgumentException->getMessage()], Response::HTTP_OK);
        }

        return $this->jsonSuccess(['folder' => $this->folderSerializer->serialize($folder)]);
    }

    #[Route('/folders/{id}/delete', name: '_folder_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteFolder(MediaFolder $folder): JsonResponse
    {
        $this->mediaManager->deleteFolder($folder);

        return $this->jsonSuccess();
    }
}
