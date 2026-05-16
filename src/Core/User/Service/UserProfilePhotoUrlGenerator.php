<?php

declare(strict_types=1);

namespace Aurora\Core\User\Service;

use Aurora\Core\User\Entity\CoreUserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Same separation-of-concerns as {@see \Aurora\Core\Media\Service\MediaUrlGenerator}
 * but for user profile photos. The entity holds the filename, this
 * service turns it into a `/uploads/profile-photos/...` URL.
 *
 * Returns `null` when the user has no photo set. See
 * `docs/aurora-core/dev/storage_policy.md` and CLAUDE.md §3bis.
 */
final readonly class UserProfilePhotoUrlGenerator
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function url(?CoreUserInterface $user): ?string
    {
        if (null === $user) {
            return null;
        }
        $path = $user->getProfilePhotoPath();
        if (null === $path || '' === $path) {
            return null;
        }

        return $this->urlGenerator->generate('uploads_serve', ['path' => 'profile-photos/'.$path]);
    }
}
