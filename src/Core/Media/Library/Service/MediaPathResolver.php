<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Library\Service;

use Aurora\Core\Media\Library\Entity\MediaInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;

use function sprintf;

/**
 * Resolves the absolute filesystem path of a Media entity stored under
 * `%app.upload_dir%`. Centralises the
 * `uploadDir.'/'.getPath()` concatenation so callers (variant generators,
 * exporters, OCR pipeline…) don't reinvent it inline.
 *
 * Throws when the file is missing on disk to surface upload/cleanup bugs
 * loudly rather than silently producing a broken downstream operation.
 */
final readonly class MediaPathResolver
{
    public function __construct(
        #[Autowire('%app.upload_dir%')]
        private string $uploadDir,
    ) {}

    public function resolveAbsolutePath(MediaInterface $media): string
    {
        $absolutePath = Path::join($this->uploadDir, $media->getPath());

        if (!is_file($absolutePath)) {
            throw new RuntimeException(sprintf('Media file missing on disk: %s', $absolutePath));
        }

        return $absolutePath;
    }
}
