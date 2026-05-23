<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Budget\Service;

use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetInterface;
use Aurora\Module\PersonalFinance\Budget\Entity\PersonalFinanceBudgetItemInterface;
use Aurora\Module\PersonalFinance\Budget\Enum\PersonalFinanceBudgetSectionEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports a budget snapshot (items + actuals computed by the
 * ViewBuilder) as an XLSX with two sheets: "Items" (one row per
 * budget line, with expected vs actual) and "Summary" (one row per
 * section with section totals + variance). Non-`final` + `readonly`
 * so a client can swap headers, palette, or columns.
 *
 * Input shape for $sections matches PersonalFinanceBudgetViewBuilder
 * ::buildShowPayload['sections']: a map keyed by section value with
 * `items: list<array>` + `planned/expected/actual` strings.
 */
readonly class PersonalFinanceBudgetXlsxExporter
{
    private const string HEADER_BG = 'FF1E3A8A';

    private const string HEADER_TEXT = 'FFFFFFFF';

    private const string SECTION_BG = 'FFF1F5F9';

    private const array ITEM_HEADERS = [
        'Section',
        'Label',
        'Category',
        'Planned',
        'Carried over',
        'Expected',
        'Actual',
        'Variance',
        'Repeat next month',
        'Notes',
    ];

    private const array SUMMARY_HEADERS = [
        'Section',
        'Planned',
        'Expected',
        'Actual',
        'Variance',
    ];

    /**
     * @param array<string, array{planned: string, expected: string, actual: string, items: list<PersonalFinanceBudgetItemInterface>}> $sections
     *                                                                                                                                                      keyed by PersonalFinanceBudgetSectionEnum value, items in display order
     * @param array<int, string>                                                                                                       $actualsByCategoryId category id → decimal sum (bcmath string)
     */
    public function buildResponse(PersonalFinanceWalletInterface $wallet, PersonalFinanceBudgetInterface $budget, array $sections, array $actualsByCategoryId): Response
    {
        $spreadsheet = $this->build($wallet, $budget, $sections, $actualsByCategoryId);
        $writer = new Xlsx($spreadsheet);
        $filename = sprintf(
            'personal-finance-budget-%s-%s.xlsx',
            $this->slugify($wallet->getName()),
            $budget->getMonth()->format('Y-m'),
        );

        $response = new StreamedResponse(static function () use ($writer): void {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));
        $response->headers->set('Cache-Control', 'no-store, max-age=0');

        return $response;
    }

    /**
     * @param array<string, array{planned: string, expected: string, actual: string, items: list<PersonalFinanceBudgetItemInterface>}> $sections
     * @param array<int, string>                                                                                                       $actualsByCategoryId
     */
    protected function build(PersonalFinanceWalletInterface $wallet, PersonalFinanceBudgetInterface $budget, array $sections, array $actualsByCategoryId): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle(sprintf('Budget %s', $budget->getMonth()->format('Y-m')))
            ->setSubject(sprintf('Budget %s — %s', $budget->getMonth()->format('Y-m'), $wallet->getName()))
            ->setCreator('Aurora');

        $itemsSheet = $spreadsheet->getActiveSheet();
        $itemsSheet->setTitle('Items');
        $this->writeItemsSheet($itemsSheet, $sections, $actualsByCategoryId);

        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Summary');
        $this->writeSummarySheet($summarySheet, $sections);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param array<string, array{planned: string, expected: string, actual: string, items: list<PersonalFinanceBudgetItemInterface>}> $sections
     * @param array<int, string>                                                                                                       $actualsByCategoryId
     */
    protected function writeItemsSheet(Worksheet $sheet, array $sections, array $actualsByCategoryId): void
    {
        $this->writeHeader($sheet, self::ITEM_HEADERS, 'J');

        $row = 2;
        foreach (PersonalFinanceBudgetSectionEnum::cases() as $sectionCase) {
            $bucket = $sections[$sectionCase->value] ?? null;
            if (null === $bucket) {
                continue;
            }

            if ([] === $bucket['items']) {
                continue;
            }

            foreach ($bucket['items'] as $item) {
                $categoryId = $item->getCategory()?->getId();
                $planned = (float) $item->getPlannedAmount();
                $carried = (float) $item->getCarriedOver();
                $actual = (float) (null === $categoryId ? '0' : ($actualsByCategoryId[$categoryId] ?? '0'));
                $expected = $planned + $carried;
                $variance = $expected - $actual;

                $sheet->setCellValue('A'.$row, $sectionCase->value);
                $sheet->setCellValue('B'.$row, $item->getLabel());
                $sheet->setCellValue('C'.$row, $item->getCategory()?->getName() ?? '');
                $sheet->setCellValue('D'.$row, $planned);
                $sheet->setCellValue('E'.$row, $carried);
                $sheet->setCellValue('F'.$row, $expected);
                $sheet->setCellValue('G'.$row, $actual);
                $sheet->setCellValue('H'.$row, $variance);
                $sheet->setCellValue('I'.$row, $item->repeatsNextMonth() ? 'yes' : 'no');
                $sheet->setCellValue('J'.$row, $item->getNotes() ?? '');
                $sheet->getStyle(sprintf('D%d:H%d', $row, $row))->getNumberFormat()->setFormatCode('#,##0.00');
                ++$row;
            }
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A2');
    }

    /**
     * @param array<string, array{planned: string, expected: string, actual: string, items: list<PersonalFinanceBudgetItemInterface>}> $sections
     */
    protected function writeSummarySheet(Worksheet $sheet, array $sections): void
    {
        $this->writeHeader($sheet, self::SUMMARY_HEADERS, 'E');

        $row = 2;
        foreach (PersonalFinanceBudgetSectionEnum::cases() as $sectionCase) {
            $bucket = $sections[$sectionCase->value] ?? null;
            if (null === $bucket) {
                continue;
            }

            $planned = (float) $bucket['planned'];
            $expected = (float) $bucket['expected'];
            $actual = (float) $bucket['actual'];
            $variance = $expected - $actual;

            $sheet->setCellValue('A'.$row, $sectionCase->value);
            $sheet->setCellValue('B'.$row, $planned);
            $sheet->setCellValue('C'.$row, $expected);
            $sheet->setCellValue('D'.$row, $actual);
            $sheet->setCellValue('E'.$row, $variance);
            $sheet->getStyle('A'.$row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::SECTION_BG]],
                'font' => ['bold' => true],
            ]);
            $sheet->getStyle(sprintf('B%d:E%d', $row, $row))->getNumberFormat()->setFormatCode('#,##0.00');
            ++$row;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A2');
    }

    /**
     * @param list<string> $headers
     */
    protected function writeHeader(Worksheet $sheet, array $headers, string $lastColumn): void
    {
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle(sprintf('A1:%s1', $lastColumn))->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => self::HEADER_TEXT]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
    }

    private function slugify(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? 'wallet';

        return mb_trim($value, '-') ?: 'wallet';
    }
}
