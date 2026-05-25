<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Service;

use Aurora\Core\Storage\Enum\MimeTypeEnum;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

use function in_array;
use function sprintf;

use const DIRECTORY_SEPARATOR;

/**
 * Filesystem-backed image storage for block notes. Same shape as
 * {@see MarkdownNoteImageService}
 * (path B, no Doctrine entity) — kept independent so each notes flavour
 * can evolve its storage rules separately. Image-type blocks store the
 * bare filename in their payload; the controller resolves it back to an
 * absolute path with per-user auth.
 *
 * Layout: `{storageDir}/{userId}/{uuid}.{ext}`.
 */
final readonly class BlockImageService
{
    public const int MAX_FILE_SIZE = 5 * 1024 * 1024;

    /** @var list<MimeTypeEnum> */
    private const array ALLOWED_MIME_TYPES = [
        MimeTypeEnum::Png,
        MimeTypeEnum::Jpeg,
        MimeTypeEnum::Webp,
        MimeTypeEnum::Gif,
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/uploads/notes-block')]
        private string $storageDir,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * @throws FileException when validation fails (bad MIME, too big)
     */
    public function store(UploadedFile $file, CoreUserInterface $user): string
    {
        $size = $file->getSize();
        if (false !== $size && $size > self::MAX_FILE_SIZE) {
            throw new FileException(sprintf('Image exceeds max size of %d bytes.', self::MAX_FILE_SIZE));
        }

        $mime = $file->getMimeType() ?? '';
        $imageMime = MimeTypeEnum::tryFrom($mime);
        if (null === $imageMime || !in_array($imageMime, self::ALLOWED_MIME_TYPES, true)) {
            throw new FileException(sprintf('Unsupported MIME type "%s".', $mime));
        }

        $filename = sprintf('%s.%s', Uuid::v4()->toRfc4122(), $imageMime->extension());

        $userDir = $this->userDir($user);
        $this->filesystem->mkdir($userDir, 0o755);

        $file->move($userDir, $filename);

        return $filename;
    }

    /**
     * @throws RuntimeException when the file is missing or outside the user dir
     */
    public function path(string $filename, CoreUserInterface $user): string
    {
        $userDir = $this->userDir($user);
        $candidate = Path::join($userDir, $filename);
        $real = realpath($candidate);

        if (false === $real) {
            throw new RuntimeException(sprintf('Image not found: %s', $filename));
        }

        $userRoot = realpath($userDir);
        if (false === $userRoot || !str_starts_with($real, $userRoot.DIRECTORY_SEPARATOR) && $real !== $userRoot) {
            throw new RuntimeException(sprintf('Image path escapes user directory: %s', $filename));
        }

        return $real;
    }

    /**
     * Idempotent: silently no-ops on a missing file so the
     * block-removal hook can run safely on partial state.
     */
    public function delete(string $filename, CoreUserInterface $user): void
    {
        try {
            $real = $this->path($filename, $user);
        } catch (RuntimeException) {
            return;
        }

        $this->filesystem->remove($real);
    }

    private function userDir(CoreUserInterface $user): string
    {
        return Path::join($this->storageDir, (string) $user->getId());
    }
}
