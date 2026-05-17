<?php

declare(strict_types=1);

namespace Aurora\Tests\Concern;

use Aurora\Core\Media\Library\Service\MediaUrlGenerator;
use Aurora\Core\Platform\User\Service\UserProfilePhotoUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Test helper: builds a `MediaUrlGenerator` /
 * `UserProfilePhotoUrlGenerator` whose generated URLs mirror the
 * historical hardcoded `/uploads/<path>` shape. Keeps URL-shape
 * assertions in serializer tests focused on serialization logic
 * rather than route plumbing.
 *
 * Both helpers stub `UrlGeneratorInterface::generate()` to return
 * `/uploads/<path>` regardless of the route name (`uploads_serve` in
 * prod), so any test asserting on a URL just keeps working.
 */
trait CreatesStorageUrlGenerators
{
    private function makeMediaUrlGenerator(): MediaUrlGenerator
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $name, array $params = []): string => '/uploads/'.($params['path'] ?? ''),
        );

        return new MediaUrlGenerator($urlGenerator);
    }

    private function makeUserProfilePhotoUrlGenerator(): UserProfilePhotoUrlGenerator
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $name, array $params = []): string => '/uploads/'.($params['path'] ?? ''),
        );

        return new UserProfilePhotoUrlGenerator($urlGenerator);
    }
}
