<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Service;

use Aurora\Core\Media\Enum\MimeTypeEnum;
use GdImage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ImageVariantGenerator
{
    public const array VARIANT_SIZES = [
        'thumbnail' => 256,
        'medium' => 800,
        'large' => 1920,
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads')]
        private string $uploadDir,
    ) {}

    /**
     * Generate all variants for a given source image.
     * Variants are generated as WebP when supported (better compression, universal browser support).
     * GIFs keep their original format to preserve animation.
     *
     * @return array<string, string> variant name → relative path (under uploads/)
     */
    public function generate(string $sourceRelativePath, string $mimeType): array
    {
        $mime = MimeTypeEnum::tryFrom($mimeType);
        if (!$mime?->isRasterImage()) {
            return [];
        }

        $sourceAbsolute = $this->uploadDir.'/'.$sourceRelativePath;
        if (!is_file($sourceAbsolute)) {
            return [];
        }

        $source = $this->load($sourceAbsolute, $mime);
        if (!$source instanceof GdImage) {
            return [];
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $extension = pathinfo($sourceRelativePath, PATHINFO_EXTENSION);
        $baseName = pathinfo($sourceRelativePath, PATHINFO_FILENAME);

        $useWebP = function_exists('imagewebp') && !$mime->supportsAnimation();
        $variantMime = $useWebP ? MimeTypeEnum::Webp : $mime;
        $variantExtension = $useWebP ? 'webp' : $extension;

        // Re-encode JPEG at quality 85 to strip metadata and reduce file size.
        if ($mime->isJpeg()) {
            imagejpeg($source, $sourceAbsolute, 85);
        }

        $generated = [];
        $largestSize = max(self::VARIANT_SIZES);
        foreach (self::VARIANT_SIZES as $variantName => $maxSide) {
            // Skip downscale when source is already smaller — EXCEPT for the
            // largest size: we always want a re-encoded "large" variant so the
            // public download path (web) never falls back to the raw source,
            // which would leak EXIF (geo/camera) on PNG/WebP originals.
            $isLargest = $maxSide === $largestSize;
            if (!$isLargest && $sourceWidth <= $maxSide && $sourceHeight <= $maxSide) {
                continue;
            }

            $shouldDownscale = $sourceWidth > $maxSide || $sourceHeight > $maxSide;
            [$targetWidth, $targetHeight] = $shouldDownscale
                ? $this->fitDimensions($sourceWidth, $sourceHeight, $maxSide)
                : [$sourceWidth, $sourceHeight];

            $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
            $this->preserveTransparency($targetImage, $variantMime);

            imagecopyresampled($targetImage, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

            $variantRelative = sprintf('variants/%s/%s.%s', $variantName, $baseName, $variantExtension);
            $variantAbsolute = $this->uploadDir.'/'.$variantRelative;

            if (!is_dir(dirname($variantAbsolute))) {
                @mkdir(dirname($variantAbsolute), 0o775, true);
            }

            $this->save($targetImage, $variantAbsolute, $variantMime);
            imagedestroy($targetImage);

            $generated[$variantName] = $variantRelative;
        }

        imagedestroy($source);

        return $generated;
    }

    /**
     * @param array<string, string> $variants
     */
    public function deleteVariants(array $variants): void
    {
        foreach ($variants as $relativePath) {
            $absolute = $this->uploadDir.'/'.$relativePath;
            if (is_file($absolute)) {
                @unlink($absolute);
            }
        }
    }

    /** @return array{0: int, 1: int} */
    private function fitDimensions(int $sourceWidth, int $sourceHeight, int $maxSide): array
    {
        if ($sourceWidth >= $sourceHeight) {
            $targetWidth = $maxSide;
            $targetHeight = max(1, (int) round($sourceHeight * ($maxSide / $sourceWidth)));
        } else {
            $targetHeight = $maxSide;
            $targetWidth = max(1, (int) round($sourceWidth * ($maxSide / $sourceHeight)));
        }

        return [$targetWidth, $targetHeight];
    }

    private function load(string $path, MimeTypeEnum $mime): ?GdImage
    {
        $resource = match (true) {
            $mime->isJpeg() => @imagecreatefromjpeg($path),
            MimeTypeEnum::Png === $mime => @imagecreatefrompng($path),
            MimeTypeEnum::Gif === $mime => @imagecreatefromgif($path),
            MimeTypeEnum::Webp === $mime => @imagecreatefromwebp($path),
            default => false,
        };

        return $resource instanceof GdImage ? $resource : null;
    }

    private function save(GdImage $image, string $path, MimeTypeEnum $mime): void
    {
        match (true) {
            $mime->isJpeg() => imagejpeg($image, $path, 85),
            MimeTypeEnum::Png === $mime => imagepng($image, $path, 6),
            MimeTypeEnum::Gif === $mime => imagegif($image, $path),
            MimeTypeEnum::Webp === $mime => imagewebp($image, $path, 85),
            default => null,
        };
    }

    private function preserveTransparency(GdImage $image, MimeTypeEnum $mime): void
    {
        if (!$mime->supportsAlpha()) {
            return;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
    }
}
