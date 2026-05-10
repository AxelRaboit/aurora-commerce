<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\Service;

use RuntimeException;

interface PdfManipulatorInterface
{
    public function isAvailable(): bool;

    /**
     * Detects AcroForm field names from a PDF file.
     *
     * @return list<array{name: string, type: string}>
     *
     * @throws RuntimeException if the manipulator is not available
     */
    public function detectFields(string $pdfPath): array;

    /**
     * Fills a PDF with field values and writes the result to outputPath.
     *
     * @param array<string, string> $fieldValues
     *
     * @throws RuntimeException if the manipulator is not available
     */
    public function fill(string $pdfPath, array $fieldValues, string $outputPath, bool $flatten = false): void;
}
