<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\Service;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PdfManipulatorInterface::class)]
final class PdFtkManipulator implements PdfManipulatorInterface
{
    private const string TMP_XFDF_PREFIX = 'aurora_pdfform_xfdf_';

    private const string TMP_VALUES_PREFIX = 'aurora_pdfform_values_';

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
        if ($flatten) {
            // Flatten via Node.js + pdf-lib: embeds a Unicode font before flattening
            // so accented/special characters render correctly (pdftk uses Latin-1 only).
            $this->fillAndFlattenWithPdfLib($pdfPath, $fieldValues, $outputPath);

            return;
        }

        if (!$this->isAvailable()) {
            throw new RuntimeException('pdftk is not installed on this server.');
        }

        $xfdfPath = tempnam(sys_get_temp_dir(), self::TMP_XFDF_PREFIX).'.xfdf';
        file_put_contents($xfdfPath, $this->buildXfdf($fieldValues));

        exec(
            'pdftk '.escapeshellarg($pdfPath)
            .' fill_form '.escapeshellarg($xfdfPath)
            .' output '.escapeshellarg($outputPath)
            .' need_appearances 2>/dev/null'
        );

        if (file_exists($xfdfPath)) {
            unlink($xfdfPath);
        }
    }

    /** @param array<string, string> $fieldValues */
    private function fillAndFlattenWithPdfLib(string $pdfPath, array $fieldValues, string $outputPath): void
    {
        $valuesPath = tempnam(sys_get_temp_dir(), self::TMP_VALUES_PREFIX).'.json';
        file_put_contents($valuesPath, json_encode($fieldValues, JSON_UNESCAPED_UNICODE));

        $script = dirname(__DIR__, 4).'/tools/pdf/fill.mjs';

        exec(
            'node '.escapeshellarg($script)
            .' '.escapeshellarg($pdfPath)
            .' '.escapeshellarg($outputPath)
            .' '.escapeshellarg($valuesPath)
            .' 2>/dev/null'
        );

        if (file_exists($valuesPath)) {
            unlink($valuesPath);
        }
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

            if (str_starts_with($line, 'FieldName:')) {
                $current['name'] = mb_trim(mb_substr($line, mb_strlen('FieldName:')));
            } elseif (str_starts_with($line, 'FieldType:')) {
                $current['rawType'] = mb_strtolower(mb_trim(mb_substr($line, mb_strlen('FieldType:'))));
            } elseif (str_starts_with($line, 'FieldFlags:')) {
                $current['flags'] = (int) mb_trim(mb_substr($line, mb_strlen('FieldFlags:')));
            }

            // Resolve type once we have both rawType and flags (flags may arrive after type)
            if (isset($current['rawType'])) {
                $flags = $current['flags'] ?? 0;
                $current['type'] = match ($current['rawType']) {
                    // Bit 15 (value 16384) = Radio flag. Radio groups have it set.
                    'button' => (($flags & 16384) !== 0) ? 'radio' : 'checkbox',
                    'choice' => 'dropdown',
                    'signature' => 'signature',
                    default => 'text',
                };
            }
        }

        if (isset($current['name'])) {
            $fields[] = ['name' => $current['name'], 'type' => $current['type'] ?? 'text'];
        }

        return $fields;
    }

    /** @param array<string, string> $fieldValues */
    private function buildXfdf(array $fieldValues): string
    {
        $fields = '';
        foreach ($fieldValues as $name => $value) {
            $encodedName = htmlspecialchars($name, ENT_XML1, 'UTF-8');
            $encodedValue = htmlspecialchars($value, ENT_XML1, 'UTF-8');
            $fields .= "    <field name=\"{$encodedName}\"><value>{$encodedValue}</value></field>\n";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<xfdf xmlns="http://ns.adobe.com/xfdf/" xml:space="preserve">'."\n"
            .'  <fields>'."\n"
            .$fields
            .'  </fields>'."\n"
            .'</xfdf>'."\n";
    }
}
