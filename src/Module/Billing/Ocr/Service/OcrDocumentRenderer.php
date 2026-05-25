<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Service;

use Aurora\Core\Storage\Enum\MimeTypeEnum;
use Aurora\Module\Billing\Ocr\Contract\DocTrClientInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;

use function sprintf;

/**
 * Resolves a raster image path that the vision model can ingest.
 *
 * - For images, returns the source path unchanged.
 * - For PDFs, asks the docTR microservice to render all pages stacked into
 *   a single PNG and caches the result at var/cache/ocr/job-<jobId>.png.
 *
 * Other mime types are rejected loudly so unsupported formats cannot
 * silently corrupt the OCR pipeline downstream.
 */
final readonly class OcrDocumentRenderer
{
    private const string CACHED_IMAGE_FILENAME_FORMAT = 'job-%d.png';

    public function __construct(
        private DocTrClientInterface $doctr,
        #[Autowire('%kernel.project_dir%/var/cache/ocr')]
        private string $renderCacheDir,
        #[Autowire('%env(int:OCR_RENDER_DPI)%')]
        private int $renderDpi = 200,
    ) {}

    /**
     * @param string $absoluteSourcePath path to the original document on disk
     * @param int    $jobId              used to namespace the rendered PNG cache file
     *
     * @throws RuntimeException when the mime type is neither an image nor a PDF
     */
    public function resolveImagePath(string $absoluteSourcePath, int $jobId): string
    {
        $rawMimeType = mime_content_type($absoluteSourcePath) ?: '';
        $mimeType = MimeTypeEnum::tryFrom($rawMimeType);

        if (null === $mimeType || (!$mimeType->isImage() && MimeTypeEnum::Pdf !== $mimeType)) {
            throw new RuntimeException(sprintf('Unsupported document mime "%s" for OCR.', $rawMimeType));
        }

        if ($mimeType->isImage()) {
            return $absoluteSourcePath;
        }

        $destinationPath = Path::join($this->renderCacheDir, sprintf(self::CACHED_IMAGE_FILENAME_FORMAT, $jobId));

        return $this->doctr->renderToPng($absoluteSourcePath, $destinationPath, $this->renderDpi);
    }
}
