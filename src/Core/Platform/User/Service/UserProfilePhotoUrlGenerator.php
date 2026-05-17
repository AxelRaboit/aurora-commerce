<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\User\Service;

use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Same separation-of-concerns as {@see MediaUrlGenerator}
 * but for user profile photos. The entity holds the filename, this
 * service turns it into a `/uploads/profile-photos/...` URL.
 *
 * Returns `null` when the user has no photo set. See
 * CLAUDE.md §5bis and CLAUDE.md §3bis.
 */
final readonly class UserProfilePhotoUrlGenerator
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function url(?CoreUserInterface $user): ?string
    {
        if (!$user instanceof CoreUserInterface) {
            return null;
        }

        $path = $user->getProfilePhotoPath();
        if (null === $path || '' === $path) {
            return null;
        }

        return $this->urlGenerator->generate('uploads_serve', ['path' => 'profile-photos/'.$path]);
    }
}
