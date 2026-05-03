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
        '/^(?P<prefix>[A-Za-z]*)(?P<year>\d{4})[-\/](?P<seq>\d+)$/',
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
            if ($parsed === null) {
                // Unrecognised format — skip rather than false-positive
                continue;
            }

            [$prefix, $separator, $width, $sequences] = $parsed;
            sort($sequences);

            $gaps = [];
            for ($i = $sequences[0]; $i <= end($sequences); ++$i) {
                if (!in_array($i, $sequences, true)) {
                    $gaps[] = $prefix.$year.$separator.str_pad((string) $i, $width, '0', STR_PAD_LEFT);
                }
            }

            $results[] = [
                'year' => $year,
                'prefix' => $prefix,
                'total' => count($sequences),
                'gaps' => $gaps,
                'status' => count($gaps) === 0 ? 'ok' : 'error',
            ];
        }

        return $results;
    }

    /**
     * @param  list<string>  $numbers
     * @return array{0: string, 1: string, 2: int, 3: list<int>}|null
     */
    private function parse(array $numbers): ?array
    {
        if (empty($numbers)) {
            return null;
        }

        foreach (self::PATTERNS as $pattern) {
            $sequences = [];
            $prefix = null;
            $separator = '';
            $width = 4;

            foreach ($numbers as $number) {
                if (!preg_match($pattern, $number, $m)) {
                    continue 2; // pattern doesn't match all numbers → try next pattern
                }
                $sequences[] = (int) $m['seq'];
                $width = max($width, strlen($m['seq']));
                if ($prefix === null) {
                    $prefix = $m['prefix'];
                    $separator = isset($m[0]) && str_contains($m[0], '/') ? '/' : '-';
                }
            }

            if (!empty($sequences)) {
                return [$prefix ?? '', $separator, $width, $sequences];
            }
        }

        return null;
    }
}
