<?php

declare(strict_types=1);

namespace Aurora\Tests\Concern;

use Aurora\Core\Storage\Service\UploadUrlGenerator;
use Aurora\Module\Media\Library\Service\MediaUrlGenerator;
use Aurora\Module\Platform\User\Service\UserProfilePhotoUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Test helper: builds a `MediaUrlGenerator` /
 * `UploadUrlGenerator` / `UserProfilePhotoUrlGenerator` whose generated
 * URLs mirror the historical hardcoded `/uploads/<path>` shape. Keeps
 * URL-shape assertions in serializer tests focused on serialization
 * logic rather than route plumbing.
 *
 * All helpers stub `UrlGeneratorInterface::generate()` to return
 * `/uploads/<path>` regardless of the route name (`uploads_serve` in
 * prod), so any test asserting on a URL just keeps working.
 */
trait CreatesStorageUrlGenerators
{
    private function makeMediaUrlGenerator(): MediaUrlGenerator
    {
        return new MediaUrlGenerator($this->makeStubbedUrlGenerator());
    }

    private function makeUploadUrlGenerator(): UploadUrlGenerator
    {
        return new UploadUrlGenerator($this->makeStubbedUrlGenerator());
    }

    private function makeUserProfilePhotoUrlGenerator(): UserProfilePhotoUrlGenerator
    {
        return new UserProfilePhotoUrlGenerator($this->makeStubbedUrlGenerator());
    }

    private function makeStubbedUrlGenerator(): UrlGeneratorInterface
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $name, array $params = []): string => '/uploads/'.($params['path'] ?? ''),
        );

        return $urlGenerator;
    }
}
