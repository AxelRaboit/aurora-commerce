<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Controller\Admin;

use Aurora\Core\Audit\Repository\AuditLogRepository;
use Aurora\Core\Audit\Serializer\AuditLogSerializer;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
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
use Aurora\Core\Validation\Service\PayloadValidator;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/media', name: 'admin_media')]
#[IsGranted('core.media.manage')]
class MediaController extends AbstractController
{
    use JsonRequestTrait;

    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly MediaFolderRepository $folderRepository,
        private readonly MediaManagerInterface $mediaManager,
        private readonly MediaSerializer $mediaSerializer,
        private readonly MediaFolderSerializer $folderSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly AuditLogSerializer $auditLogSerializer,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        $serializer = $this->folderSerializer->withMediaCounts($this->mediaRepository->countGroupedByFolders());

        $folders = array_map(
            $serializer->serialize(...),
            $this->folderRepository->findAllOrdered(),
        );

        $folderId = $request->query->getInt('folderId') ?: null;
        $search = mb_trim((string) $request->query->get('search', ''));
        $folder = null !== $folderId ? $this->folderRepository->find($folderId) : null;

        $media = array_map(
            $this->mediaSerializer->serialize(...),
            $this->mediaRepository->findByFolder($folder, $search ?: null),
        );

        return $this->render('@Core/admin/media/index.html.twig', [
            'folders' => $folders,
            'media' => $media,
            'currentFolderId' => $folderId,
            'search' => $search,
            'totalStorageBytes' => $this->mediaRepository->getTotalStorageSize(),
        ]);
    }

    #[Route('/{id}/info', name: '_info', methods: [HttpMethodEnum::Get->value])]
    public function info(Media $media): JsonResponse
    {
        return $this->json(['media' => $this->mediaSerializer->serialize($media)]);
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

        $items = array_map(
            $this->mediaSerializer->serialize(...),
            $this->mediaRepository->findByFolder($folder, $search ?: null),
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
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $this->mediaManager->update($media, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['folderId' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'media' => $this->mediaSerializer->serialize($media)]);
    }

    #[Route('/{id}/move', name: '_move', methods: [HttpMethodEnum::Post->value])]
    public function move(Media $media, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $folderId = isset($data['folderId']) && (int) $data['folderId'] > 0 ? (int) $data['folderId'] : null;
        $folder = null !== $folderId ? $this->folderRepository->find($folderId) : null;

        $this->mediaManager->move($media, $folder);

        return $this->json(['success' => true, 'media' => $this->mediaSerializer->serialize($media)]);
    }

    #[Route('/reorder', name: '_reorder', methods: [HttpMethodEnum::Post->value])]
    public function reorder(Request $request): JsonResponse
    {
        $ids = $this->decodeJson($request)['ids'] ?? [];
        $this->mediaManager->reorder($ids);

        return $this->json(['success' => true]);
    }

    #[Route('/bulk-delete', name: '_bulk_delete', methods: [HttpMethodEnum::Post->value])]
    public function bulkDelete(Request $request): JsonResponse
    {
        $ids = $this->decodeJson($request)['ids'] ?? [];
        $this->mediaManager->bulkDelete($ids);

        return $this->json(['success' => true]);
    }

    #[Route('/bulk-move', name: '_bulk_move', methods: [HttpMethodEnum::Post->value])]
    public function bulkMove(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $ids = $data['ids'] ?? [];
        $folderId = $data['folderId'] ?? null;
        $folder = null !== $folderId ? $this->folderRepository->find($folderId) : null;
        $this->mediaManager->bulkMove($ids, $folder);

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Media $media): JsonResponse
    {
        $this->mediaManager->delete($media);

        return $this->json(['success' => true]);
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

        return $this->json(['success' => true, 'media' => $this->mediaSerializer->serialize($media)]);
    }

    #[Route('/folders', name: '_folder_create', methods: [HttpMethodEnum::Post->value])]
    public function createFolder(Request $request): JsonResponse
    {
        $input = MediaFolderInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $folder = $this->mediaManager->createFolder($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['parentId' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'folder' => $this->folderSerializer->serialize($folder)]);
    }

    #[Route('/folders/{id}/edit', name: '_folder_edit', methods: [HttpMethodEnum::Post->value])]
    public function editFolder(MediaFolder $folder, Request $request): JsonResponse
    {
        $input = MediaFolderInput::fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        try {
            $this->mediaManager->updateFolder($folder, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->json(['success' => false, 'errors' => ['parentId' => $invalidArgumentException->getMessage()]]);
        }

        return $this->json(['success' => true, 'folder' => $this->folderSerializer->serialize($folder)]);
    }

    #[Route('/folders/{id}/delete', name: '_folder_delete', methods: [HttpMethodEnum::Post->value])]
    public function deleteFolder(MediaFolder $folder): JsonResponse
    {
        $this->mediaManager->deleteFolder($folder);

        return $this->json(['success' => true]);
    }
}
