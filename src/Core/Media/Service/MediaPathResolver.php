<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Service;

use Aurora\Core\Media\Entity\Media;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;

/**
 * Resolves the absolute filesystem path of a Media entity stored under
 * `%kernel.project_dir%/public/uploads`. Centralises the
 * `uploadDir.'/'.getPath()` concatenation so callers (variant generators,
 * exporters, OCR pipeline…) don't reinvent it inline.
 *
 * Throws when the file is missing on disk to surface upload/cleanup bugs
 * loudly rather than silently producing a broken downstream operation.
 */
final readonly class MediaPathResolver
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads')]
        private string $uploadDir,
    ) {}

    public function resolveAbsolutePath(Media $media): string
    {
        $absolutePath = Path::join($this->uploadDir, $media->getPath());

        if (!is_file($absolutePath)) {
            throw new \RuntimeException(\sprintf('Media file missing on disk: %s', $absolutePath));
        }

        return $absolutePath;
    }
}
