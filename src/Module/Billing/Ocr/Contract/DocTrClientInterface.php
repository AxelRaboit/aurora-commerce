<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Contract;

use RuntimeException;

interface DocTrClientInterface
{
    /**
     * @return array{pages: list<array<string, mixed>>, text: string}
     *
     * @throws RuntimeException on transport, HTTP, or decoding errors
     */
    public function extract(string $absolutePath): array;

    /**
     * Render a PDF (or image) to a PNG on disk and return the destination path.
     * For multi-page PDFs the microservice stacks pages vertically into one image.
     *
     * @throws RuntimeException on transport, HTTP, or filesystem errors
     */
    public function renderToPng(string $absolutePath, string $destinationPath, int $dpi = 200): string;
}
