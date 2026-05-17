<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Library\Controller\Backend;

use Aurora\Module\Dev\Audit\Repository\AuditLogRepository;
use Aurora\Module\Dev\Audit\Serializer\AuditLogSerializer;
use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Media\Library\Dto\MediaInputFactoryInterface;
use Aurora\Core\Media\Library\Entity\Media;
use Aurora\Core\Media\Library\Enum\MimeTypeEnum;
use Aurora\Core\Media\Library\Manager\MediaManagerInterface;
use Aurora\Core\Media\Library\Repository\MediaFolderRepository;
use Aurora\Core\Media\Library\Repository\MediaRepository;
use Aurora\Core\Media\Library\Serializer\MediaFolderSerializerInterface;
use Aurora\Core\Media\Library\Serializer\MediaSerializerInterface;
use Aurora\Core\Media\Library\Service\MediaUrlGenerator;
use Aurora\Core\Media\Library\Service\MediaUsageService;
use Aurora\Core\Media\Library\View\MediaViewBuilder;
use Aurora\Core\Validation\Service\PayloadValidator;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/media', name: 'backend_media')]
#[IsGranted('media.view')]
class MediaController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly MediaFolderRepository $folderRepository,
        private readonly MediaManagerInterface $mediaManager,
        private readonly MediaSerializerInterface $mediaSerializer,
        private readonly MediaFolderSerializerInterface $folderSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly AuditLogSerializer $auditLogSerializer,
        private readonly MediaUsageService $mediaUsageService,
        private readonly MediaViewBuilder $viewBuilder,
        private readonly MediaInputFactoryInterface $mediaInputFactory,
        protected readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(Request $request): Response
    {
        $folderId = $request->query->getInt('folderId') ?: null;
        $search = mb_trim((string) $request->query->get('search', ''));

        return $this->render('@Core/backend/media/index.html.twig', $this->viewBuilder->indexView($folderId, $search));
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

        return $this->jsonSuccess([
            'directCount' => $counts['directCount'],
            'contentCount' => $counts['contentCount'],
            'total' => $detailed['total'],
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
    #[IsGranted('media.create')]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('image');
        if (!$file) {
            return $this->json(['success' => 0, 'message' => 'No file provided.'], HttpStatusEnum::BadRequest->value);
        }

        if (null === MimeTypeEnum::tryFrom($file->getMimeType() ?? '')) {
            return $this->json(['success' => 0, 'message' => 'Invalid file type.'], HttpStatusEnum::UnprocessableEntity->value);
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
                'url' => $this->mediaUrlGenerator->publicUrl($media),
                'width' => $media->getWidth(),
                'height' => $media->getHeight(),
            ],
            'media' => $this->mediaSerializer->serialize($media),
        ]);
    }

    #[Route('/{id}/update', name: '_update', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('media.edit')]
    public function edit(Media $media, Request $request): JsonResponse
    {
        $input = $this->mediaInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->mediaManager->update($media, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['folderId' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['media' => $this->mediaSerializer->serialize($media)]);
    }

    #[Route('/{id}/move', name: '_move', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('media.edit')]
    public function move(Media $media, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        $folderId = isset($data['folderId']) && (int) $data['folderId'] > 0 ? (int) $data['folderId'] : null;
        $folder = null !== $folderId ? $this->folderRepository->find($folderId) : null;

        $this->mediaManager->move($media, $folder);

        return $this->jsonSuccess(['media' => $this->mediaSerializer->serialize($media)]);
    }

    #[Route('/reorder', name: '_reorder', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('media.edit')]
    public function reorder(Request $request): JsonResponse
    {
        $ids = $this->decodeJson($request)['ids'] ?? [];
        $this->mediaManager->reorder($ids);

        return $this->jsonSuccess();
    }

    #[Route('/bulk-delete', name: '_bulk_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('media.delete')]
    public function bulkDelete(Request $request): JsonResponse
    {
        $ids = $this->decodeJson($request)['ids'] ?? [];
        $this->mediaManager->bulkDelete($ids);

        return $this->jsonSuccess();
    }

    #[Route('/bulk-move', name: '_bulk_move', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('media.edit')]
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
    #[IsGranted('media.delete')]
    public function delete(Media $media): JsonResponse
    {
        $this->mediaManager->delete($media);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/crop', name: '_crop', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('media.edit')]
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
}
