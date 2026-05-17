<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Notes\Block\Service\BlockImageService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/notes/block/images', name: 'backend_notes_block_images')]
#[IsGranted('notes.block.use')]
final class BlockNotesImagesController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly BlockImageService $imageService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    #[Route('/upload', name: '_upload', methods: [HttpMethodEnum::Post->value])]
    public function upload(Request $request): JsonResponse
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        $file = $request->files->get('image');
        if (!$file instanceof UploadedFile) {
            return $this->jsonInvalidInput(['image' => 'Missing or invalid upload.']);
        }

        try {
            $filename = $this->imageService->store($file, $user);
        } catch (FileException $fileException) {
            return $this->jsonInvalidInput(['image' => $fileException->getMessage()]);
        }

        // Editor.js' Image tool shape — `file.filename` is a Notes-specific
        // extension propagated back to the block payload so the per-user
        // upload-cleanup hook (`BlockNoteManager::imageFilename()`) can
        // diff old vs new and delete orphans.
        return new JsonResponse([
            'success' => 1,
            'file' => [
                'url' => $this->urlGenerator->generate('backend_notes_block_images_serve', ['filename' => $filename]),
                'filename' => $filename,
            ],
        ]);
    }

    #[Route(
        '/{filename}',
        name: '_serve',
        requirements: ['filename' => '[A-Za-z0-9._-]+'],
        methods: [HttpMethodEnum::Get->value],
    )]
    public function serve(string $filename): Response
    {
        /** @var CoreUserInterface $user */
        $user = $this->getUser();

        try {
            $path = $this->imageService->path($filename, $user);
        } catch (RuntimeException) {
            return $this->jsonNotFound();
        }

        $response = new BinaryFileResponse($path);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, max-age=3600');

        return $response;
    }
}
