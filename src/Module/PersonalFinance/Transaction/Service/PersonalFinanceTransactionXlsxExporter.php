<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Service;

use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a transaction list as an XLSX. Money columns are emitted
 * as native numbers (signed by type) so spreadsheet tools sum /
 * filter / pivot them naturally. Mirrors the pattern of
 * InvoiceXlsxExporter (Billing).
 *
 * Non-`final` + `readonly` props per the service convention — a
 * client can subclass to swap the header palette, add columns,
 * change the filename suffix, etc.
 */
readonly class PersonalFinanceTransactionXlsxExporter
{
    private const string HEADER_BG = 'FF1E3A8A';

    private const string HEADER_TEXT = 'FFFFFFFF';

    private const array HEADERS = [
        'ID',
        'Date',
        'Type',
        'Amount',
        'Category',
        'Description',
        'Tags',
    ];

    /**
     * @param iterable<PersonalFinanceTransactionInterface> $transactions
     */
    public function buildResponse(PersonalFinanceWalletInterface $wallet, iterable $transactions): Response
    {
        $spreadsheet = $this->build($wallet, $transactions);
        $writer = new Xlsx($spreadsheet);
        $filename = sprintf(
            'personal-finance-transactions-%s-%s.xlsx',
            $this->slugify($wallet->getName()),
            new DateTimeImmutable()->format('Y-m-d'),
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
     * @param iterable<PersonalFinanceTransactionInterface> $transactions
     */
    protected function build(PersonalFinanceWalletInterface $wallet, iterable $transactions): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Transactions')
            ->setSubject(sprintf('Transactions — %s', $wallet->getName()))
            ->setCreator('Aurora');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Transactions');

        $this->writeHeader($sheet);

        $row = 2;
        foreach ($transactions as $tx) {
            $this->writeTransaction($sheet, $row, $tx);
            ++$row;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        return $spreadsheet;
    }

    protected function writeHeader(Worksheet $sheet): void
    {
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => self::HEADER_TEXT]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
    }

    protected function writeTransaction(Worksheet $sheet, int $row, PersonalFinanceTransactionInterface $transaction): void
    {
        $sign = PersonalFinanceTransactionTypeEnum::Expense === $transaction->getType() ? -1 : 1;
        $signedAmount = $sign * (float) $transaction->getAmount();

        $sheet->setCellValue('A'.$row, $transaction->getId());
        $sheet->setCellValue('B'.$row, $transaction->getDate()->format('Y-m-d'));
        $sheet->setCellValue('C'.$row, $transaction->getType()->value);
        $sheet->setCellValue('D'.$row, $signedAmount);
        $sheet->getStyle('D'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->setCellValue('E'.$row, $transaction->getCategory()?->getName() ?? '');
        $sheet->setCellValue('F'.$row, $transaction->getDescription() ?? '');
        $sheet->setCellValue('G'.$row, implode(', ', $transaction->getTags()));
    }

    private function slugify(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? 'wallet';

        return mb_trim($value, '-') ?: 'wallet';
    }
}
