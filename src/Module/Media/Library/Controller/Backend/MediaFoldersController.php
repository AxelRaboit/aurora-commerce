<?php

declare(strict_types=1);

namespace Aurora\Module\Media\Library\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonRequestTrait;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Media\Library\Dto\MediaFolderInputFactoryInterface;
use Aurora\Module\Media\Library\Entity\MediaFolder;
use Aurora\Module\Media\Library\Manager\MediaManagerInterface;
use Aurora\Module\Media\Library\Serializer\MediaFolderSerializerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Media folders sub-domain — create / edit / delete folders. Split from
 * `MediaController` to keep the two concerns isolated (media items vs
 * folder tree). Route names preserved (`backend_media_folder_*`).
 */
#[Route('/backend/media', name: 'backend_media')]
final class MediaFoldersController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly MediaManagerInterface $mediaManager,
        private readonly MediaFolderSerializerInterface $folderSerializer,
        private readonly MediaFolderInputFactoryInterface $folderInputFactory,
        private readonly PayloadValidator $payloadValidator,
    ) {}

    #[Route('/folders', name: '_folder_create', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('media.folders.create')]
    public function create(Request $request): JsonResponse
    {
        $input = $this->folderInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $folder = $this->mediaManager->createFolder($input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['parentId' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['folder' => $this->folderSerializer->serialize($folder)]);
    }

    #[Route('/folders/{id}/edit', name: '_folder_edit', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('media.folders.edit')]
    public function edit(MediaFolder $folder, Request $request): JsonResponse
    {
        $input = $this->folderInputFactory->fromArray($this->decodeJson($request));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        try {
            $this->mediaManager->updateFolder($folder, $input);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return $this->jsonInvalidInput(['parentId' => $invalidArgumentException->getMessage()]);
        }

        return $this->jsonSuccess(['folder' => $this->folderSerializer->serialize($folder)]);
    }

    #[Route('/folders/{id}/delete', name: '_folder_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('media.folders.delete')]
    public function delete(MediaFolder $folder): JsonResponse
    {
        $this->mediaManager->deleteFolder($folder);

        return $this->jsonSuccess();
    }
}
