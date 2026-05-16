<?php

declare(strict_types=1);

namespace Aurora\Core\Twig;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\Media\Service\MediaUrlGenerator;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Core\User\Service\UserProfilePhotoUrlGenerator;
use Twig\Attribute\AsTwigFunction;

/**
 * Twig bridge over the storage URL generators. Templates use:
 *
 *     {{ aurora_media_url(media) }}
 *     {{ aurora_media_url(media, 'thumbnail') }}
 *     {{ aurora_profile_photo_url(user) }}
 *
 * `media.publicUrl` / `user.profilePhotoUrl` no longer exist on
 * entities — URL building was moved into dedicated services to keep
 * the domain model free of HTTP concerns. See
 * `docs/aurora-core/dev/storage_policy.md` and CLAUDE.md §3bis.
 */
final class StorageUrlExtension
{
    public function __construct(
        private readonly MediaUrlGenerator $mediaUrlGenerator,
        private readonly UserProfilePhotoUrlGenerator $userProfilePhotoUrlGenerator,
    ) {}

    #[AsTwigFunction(name: 'aurora_media_url')]
    public function mediaUrl(?MediaInterface $media, ?string $variant = null): ?string
    {
        return null === $variant
            ? $this->mediaUrlGenerator->publicUrl($media)
            : $this->mediaUrlGenerator->variantUrl($media, $variant);
    }

    #[AsTwigFunction(name: 'aurora_profile_photo_url')]
    public function profilePhotoUrl(?CoreUserInterface $user): ?string
    {
        return $this->userProfilePhotoUrlGenerator->url($user);
    }
}
