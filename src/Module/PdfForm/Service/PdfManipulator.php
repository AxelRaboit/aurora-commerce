<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\Service;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Hybrid PDF AcroForm manipulator:
 * - `detectFields()` uses pdftk (`dump_data_fields`) — fast, robust schema introspection.
 * - `fill()` delegates to a Node.js + pdf-lib script (tools/pdf/fill.mjs) which embeds a
 *   Unicode font, force-regenerates appearances (so radios/checkboxes render even when
 *   the source PDF's original /AP streams break in PDF.js or during flatten), and
 *   optionally flattens.
 */
#[AsAlias(PdfManipulatorInterface::class)]
final class PdfManipulator implements PdfManipulatorInterface
{
    private const string TMP_VALUES_PREFIX = 'aurora_pdfform_values_';

    private const int RADIO_FLAG_BIT = 16384;

    private ?bool $available = null;

    public function isAvailable(): bool
    {
        if (null !== $this->available) {
            return $this->available;
        }

        exec('which pdftk 2>/dev/null', $out, $code);

        return $this->available = (0 === $code && [] !== $out);
    }

    public function detectFields(string $pdfPath): array
    {
        if (!$this->isAvailable()) {
            throw new RuntimeException('pdftk is not installed on this server.');
        }

        $output = [];
        exec('pdftk '.escapeshellarg($pdfPath).' dump_data_fields 2>/dev/null', $output);

        return $this->parseFields($output);
    }

    public function fill(string $pdfPath, array $fieldValues, string $outputPath, bool $flatten = false): void
    {
        $valuesPath = $this->writeValuesFile($fieldValues);

        try {
            exec($this->buildFillCommand($pdfPath, $outputPath, $valuesPath, $flatten).' 2>/dev/null');
        } finally {
            if (file_exists($valuesPath)) {
                unlink($valuesPath);
            }
        }
    }

    /** @param array<string, string> $fieldValues */
    private function writeValuesFile(array $fieldValues): string
    {
        $valuesPath = tempnam(sys_get_temp_dir(), self::TMP_VALUES_PREFIX).'.json';
        file_put_contents($valuesPath, json_encode($fieldValues, JSON_UNESCAPED_UNICODE));

        return $valuesPath;
    }

    private function buildFillCommand(string $pdfPath, string $outputPath, string $valuesPath, bool $flatten): string
    {
        $script = dirname(__DIR__, 4).'/tools/pdf/fill.mjs';

        $cmd = 'node '.escapeshellarg($script)
            .' '.escapeshellarg($pdfPath)
            .' '.escapeshellarg($outputPath)
            .' '.escapeshellarg($valuesPath);

        return $flatten ? $cmd.' --flatten' : $cmd;
    }

    /** @param list<string> $lines */
    private function parseFields(array $lines): array
    {
        $fields = [];
        $current = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '---')) {
                if (isset($current['name'])) {
                    $fields[] = ['name' => $current['name'], 'type' => $current['type'] ?? 'text'];
                }

                $current = [];
                continue;
            }

            $this->parseFieldLine($line, $current);
            $this->resolveFieldType($current);
        }

        if (isset($current['name'])) {
            $fields[] = ['name' => $current['name'], 'type' => $current['type'] ?? 'text'];
        }

        return $fields;
    }

    /** @param array<string, mixed> $current */
    private function parseFieldLine(string $line, array &$current): void
    {
        if (str_starts_with($line, 'FieldName:')) {
            $current['name'] = mb_trim(mb_substr($line, mb_strlen('FieldName:')));
        } elseif (str_starts_with($line, 'FieldType:')) {
            $current['rawType'] = mb_strtolower(mb_trim(mb_substr($line, mb_strlen('FieldType:'))));
        } elseif (str_starts_with($line, 'FieldFlags:')) {
            $current['flags'] = (int) mb_trim(mb_substr($line, mb_strlen('FieldFlags:')));
        }
    }

    /** @param array<string, mixed> $current */
    private function resolveFieldType(array &$current): void
    {
        if (!isset($current['rawType'])) {
            return;
        }

        $flags = $current['flags'] ?? 0;
        $current['type'] = match ($current['rawType']) {
            'button' => 0 !== ($flags & self::RADIO_FLAG_BIT) ? 'radio' : 'checkbox',
            'choice' => 'dropdown',
            'signature' => 'signature',
            default => 'text',
        };
    }
}
