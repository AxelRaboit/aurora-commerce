<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Library\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Enum\HttpStatusEnum;
use Aurora\Core\Media\Library\Entity\Media;
use Aurora\Core\Media\Library\Service\MediaUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Provides stable canonical URLs for media files.
 * /media/{id} always redirects to the current file regardless of renames or crops.
 */
class MediaViewController extends AbstractController
{
    public function __construct(
        protected readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {}

    #[Route(
        '/media/{id}',
        name: 'media_view',
        requirements: ['id' => '\d+'],
        methods: [HttpMethodEnum::Get->value],
    )]
    public function view(Media $media): RedirectResponse
    {
        return $this->redirect(
            $this->mediaUrlGenerator->publicUrl($media).'?v='.$media->getUpdatedAt()->getTimestamp(),
            HttpStatusEnum::Found->value,
        );
    }
}
