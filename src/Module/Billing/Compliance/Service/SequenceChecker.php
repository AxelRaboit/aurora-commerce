<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Compliance\Service;

use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;

/**
 * Detects gaps in invoice number sequences.
 *
 * Supports common French formats: YYYY-NNNN, FAYYYY-NNNN, FAYYYY/NNNN,
 * YYYY/NNNN, YYYYNNNN. Returns null for unrecognised formats so that false
 * positives are never reported.
 */
final readonly class SequenceChecker
{
    /** Patterns: named group "prefix" (optional) + "year" + separator + "seq" */
    private const array PATTERNS = [
        // PREFIX-YYYY-NNN or PREFIX/YYYY/NNN (prefix with separator before year)
        '/^(?P<prefix>[A-Za-z]+)[-\/](?P<year>\d{4})[-\/](?P<seq>\d+)$/',
        // PREFIXYYYYYYY-NNN or PREFIX/NNN (no separator before year)
        '/^(?P<prefix>[A-Za-z]*)(?P<year>\d{4})[-\/](?P<seq>\d+)$/',
        // PREFIXYYYYNNNN (no separator at all)
        '/^(?P<prefix>[A-Za-z]*)(?P<year>\d{4})(?P<seq>\d{4,6})$/',
    ];

    public function __construct(private InvoiceRepository $invoiceRepository) {}

    /**
     * @return list<array{year: int, prefix: string, gaps: list<string>, total: int}>
     */
    public function check(): array
    {
        $years = $this->invoiceRepository->findInvoiceNumbersByYear();
        $results = [];

        foreach ($years as $year => $numbers) {
            $parsed = $this->parse($numbers);
            if (null === $parsed) {
                // Unrecognised format — skip rather than false-positive
                continue;
            }

            [$prefix, $separator, $width, $sequences] = $parsed;
            sort($sequences);

            $gaps = [];
            for ($i = $sequences[0]; $i <= end($sequences); ++$i) {
                if (!in_array($i, $sequences, true)) {
                    $gaps[] = $prefix.$year.$separator.mb_str_pad((string) $i, $width, '0', STR_PAD_LEFT);
                }
            }

            $results[] = [
                'year' => $year,
                'prefix' => $prefix,
                'total' => count($sequences),
                'gaps' => $gaps,
                'status' => [] === $gaps ? 'ok' : 'error',
            ];
        }

        return $results;
    }

    /**
     * @param list<string> $numbers
     *
     * @return array{0: string, 1: string, 2: int, 3: list<int>}|null
     */
    private function parse(array $numbers): ?array
    {
        if ([] === $numbers) {
            return null;
        }

        foreach (self::PATTERNS as $pattern) {
            $sequences = [];
            $prefix = null;
            $separator = '';
            $width = 4;

            $prefixSep = '';
            foreach ($numbers as $number) {
                if (!preg_match($pattern, $number, $m)) {
                    continue 2; // pattern doesn't match all numbers → try next pattern
                }

                $sequences[] = (int) $m['seq'];
                $width = max($width, mb_strlen($m['seq']));
                if (null === $prefix) {
                    $prefix = $m['prefix'];
                    // Detect separators: prefix→year and year→seq
                    $rest = mb_substr($number, mb_strlen($prefix));
                    $prefixSep = ('' !== $prefix && str_starts_with($rest, '-')) ? '-'
                        : (('' !== $prefix && str_starts_with($rest, '/')) ? '/' : '');
                    $separator = str_contains(mb_substr($rest, mb_strlen($prefixSep) + 4), '/') ? '/' : '-';
                }
            }

            // Rebuild format: PREFIX{prefixSep}YEAR{separator}SEQ
            $fullPrefix = '' !== $prefix ? $prefix.$prefixSep : '';

            return [$fullPrefix, $separator, $width, $sequences];
        }

        return null;
    }
}
