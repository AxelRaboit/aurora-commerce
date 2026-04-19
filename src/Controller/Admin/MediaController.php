<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Contract\MediaManagerInterface;
use App\Enum\HttpMethodEnum;
use App\Enum\MimeTypeEnum;
use App\Enum\UserRoleEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/media', name: 'admin_media')]
#[IsGranted(UserRoleEnum::Admin->value)]
class MediaController extends AbstractController
{
    public function __construct(
        private readonly MediaManagerInterface $mediaManager,
    ) {}

    #[Route('', name: '')]
    public function index(): Response
    {
        return $this->render('admin/media/index.html.twig');
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

        $media = $this->mediaManager->upload($file);

        return $this->json([
            'success' => 1,
            'file' => [
                'url' => $media->getPublicUrl(),
                'id' => $media->getId(),
                'width' => $media->getWidth(),
                'height' => $media->getHeight(),
            ],
        ]);
    }
}
