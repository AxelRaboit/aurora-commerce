<?php

declare(strict_types=1);

namespace Aurora\Core\Media\Service;

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
     * Variants use the same extension as the source; for oversized source
     * images only: variants smaller than source are produced, others skipped.
     *
     * @return array<string, string> variant name → relative path (under uploads/)
     */
    public function generate(string $sourceRelativePath, string $mimeType): array
    {
        if (!str_starts_with($mimeType, 'image/')) {
            return [];
        }

        $sourceAbsolute = $this->uploadDir.'/'.$sourceRelativePath;
        if (!is_file($sourceAbsolute)) {
            return [];
        }

        $source = $this->load($sourceAbsolute, $mimeType);
        if (!$source instanceof GdImage) {
            return [];
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $extension = pathinfo($sourceRelativePath, PATHINFO_EXTENSION);
        $baseName = pathinfo($sourceRelativePath, PATHINFO_FILENAME);

        // Use WebP for all variants when supported (better compression, universal browser support).
        // GIFs keep their original format to preserve animation.
        $useWebP = function_exists('imagewebp') && 'image/gif' !== $mimeType;
        $variantExtension = $useWebP ? 'webp' : $extension;
        $variantMime = $useWebP ? 'image/webp' : $mimeType;

        // Compress source JPEG on upload (re-encode at quality 85 to strip metadata & reduce size).
        if (in_array($mimeType, ['image/jpeg', 'image/jpg'], true)) {
            imagejpeg($source, $sourceAbsolute, 85);
        }

        $generated = [];
        foreach (self::VARIANT_SIZES as $variantName => $maxSide) {
            if ($sourceWidth <= $maxSide && $sourceHeight <= $maxSide) {
                continue; // no upscaling
            }

            [$targetWidth, $targetHeight] = $this->fitDimensions($sourceWidth, $sourceHeight, $maxSide);

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

    private function load(string $path, string $mimeType): ?GdImage
    {
        $resource = match ($mimeType) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };

        return $resource instanceof GdImage ? $resource : null;
    }

    private function save(GdImage $image, string $path, string $mimeType): void
    {
        match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($image, $path, 85),
            'image/png' => imagepng($image, $path, 6),
            'image/gif' => imagegif($image, $path),
            'image/webp' => imagewebp($image, $path, 85),
            default => null,
        };
    }

    private function preserveTransparency(GdImage $image, string $mimeType): void
    {
        if (!in_array($mimeType, ['image/png', 'image/gif', 'image/webp'], true)) {
            return;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
    }
}
