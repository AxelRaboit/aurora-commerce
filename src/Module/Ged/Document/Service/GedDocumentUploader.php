<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Service;

use Aurora\Core\Storage\Service\PdfThumbnailGenerator;
use Aurora\Module\Media\Library\Enum\MimeTypeEnum;
use Aurora\Module\Media\Library\Enum\StorageAreaEnum;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Owns the on-disk handling for GED documents — slugifies the upload name,
 * picks a `var/uploads/ged/Y/m/<slug>-<uniqid>.<ext>` destination, moves
 * the uploaded bytes, returns the metadata the form needs to persist on
 * the `Document` entity.
 *
 * Kept as a thin standalone service (no entity coupling) so the controller's
 * `/upload` endpoint can call it without going through the manager. The
 * actual `Document` row creation happens on the form submit, with the
 * `filePath` carried in the DocumentInput DTO.
 */
final readonly class GedDocumentUploader
{
    public function __construct(
        private Filesystem $filesystem,
        private SluggerInterface $slugger,
        private PdfThumbnailGenerator $pdfThumbnailGenerator,
        #[Autowire('%app.upload_dir%')]
        private string $uploadDir,
    ) {}

    /**
     * @return array{filePath: string, fileName: string, originalName: string, mimeType: string, size: int, thumbnailPath: string|null}
     */
    public function upload(UploadedFile $file): array
    {
        $mimeType = (string) $file->getMimeType();
        $size = (int) $file->getSize();
        $clientName = $file->getClientOriginalName();

        $safeFilename = $this->slugger->slug(pathinfo($clientName, PATHINFO_FILENAME))->lower();
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
        $dateSlug = new DateTimeImmutable()->format('Y/m');
        $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid(), $extension);
        $relativeDir = sprintf('%s/%s', StorageAreaEnum::Ged->value, $dateSlug);
        $relativePath = sprintf('%s/%s', $relativeDir, $newFilename);

        $this->filesystem->mkdir(Path::join($this->uploadDir, $relativeDir));
        $file->move(Path::join($this->uploadDir, $relativeDir), $newFilename);

        $thumbnailPath = null;
        if (MimeTypeEnum::Pdf->value === $mimeType) {
            $thumbDir = sprintf('%s/thumbnails/%s', StorageAreaEnum::Ged->value, $dateSlug);
            $thumbBasename = pathinfo($newFilename, PATHINFO_FILENAME);
            $thumbnailPath = $this->pdfThumbnailGenerator->generate($relativePath, $thumbDir, $thumbBasename);
        }

        return [
            'filePath' => $relativePath,
            'fileName' => $newFilename,
            'originalName' => $clientName,
            'mimeType' => $mimeType,
            'size' => $size,
            'thumbnailPath' => $thumbnailPath,
        ];
    }
}
