<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Service;

use Aurora\Module\Billing\Invoice\Entity\Invoice;
use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function sprintf;

/**
 * Streams an Invoice list as an XLSX. Money columns are emitted as native
 * numbers so spreadsheet tools sum/filter them naturally.
 */
final readonly class InvoiceXlsxExporter
{
    private const string HEADER_BG = 'FF1E3A8A';

    private const string HEADER_TEXT = 'FFFFFFFF';

    private const array HEADERS = [
        'ID',
        'Numéro',
        'Statut',
        'Fournisseur',
        'N° TVA fournisseur',
        'Émise le',
        'Échéance',
        'Devise',
        'Total HT',
        'TVA',
        'Total TTC',
        'Mode de paiement',
        'Conditions de paiement',
    ];

    /**
     * @param iterable<Invoice> $invoices
     */
    public function buildResponse(iterable $invoices): Response
    {
        $spreadsheet = $this->build($invoices);
        $writer = new Xlsx($spreadsheet);
        $filename = sprintf('invoices-%s.xlsx', new DateTimeImmutable()->format('Y-m-d'));

        $response = new StreamedResponse(static function () use ($writer): void {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));
        $response->headers->set('Cache-Control', 'no-store, max-age=0');

        return $response;
    }

    /**
     * @param iterable<Invoice> $invoices
     */
    private function build(iterable $invoices): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Factures')
            ->setSubject('Export factures')
            ->setCreator('Aurora');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Factures');

        $this->writeHeader($sheet);

        $row = 2;
        foreach ($invoices as $invoice) {
            $this->writeInvoice($sheet, $row, $invoice);
            ++$row;
        }

        // Auto-size columns (skip if no data row).
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        return $spreadsheet;
    }

    private function writeHeader(Worksheet $sheet): void
    {
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => self::HEADER_TEXT]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
    }

    private function writeInvoice(Worksheet $sheet, int $row, Invoice $invoice): void
    {
        $supplier = $invoice->getSupplier();
        $currency = $invoice->getCurrency()->value;

        $sheet->setCellValue('A'.$row, $invoice->getId());
        $sheet->setCellValue('B'.$row, $invoice->getNumber() ?? '');
        $sheet->setCellValue('C'.$row, $invoice->getStatus()->value);
        $sheet->setCellValue('D'.$row, $supplier?->getName() ?? '');
        $sheet->setCellValue('E'.$row, $supplier?->getVatNumber() ?? '');
        $sheet->setCellValue('F'.$row, $invoice->getIssuedAt()?->format('Y-m-d') ?? '');
        $sheet->setCellValue('G'.$row, $invoice->getDueAt()?->format('Y-m-d') ?? '');
        $sheet->setCellValue('H'.$row, $currency);

        $this->setMoneyCell($sheet, 'I'.$row, $invoice->getTotalNetCents(), $currency);
        $this->setMoneyCell($sheet, 'J'.$row, $invoice->getTotalVatCents(), $currency);
        $this->setMoneyCell($sheet, 'K'.$row, $invoice->getTotalGrossCents(), $currency);

        $sheet->setCellValue('L'.$row, $invoice->getPaymentMethod() ?? '');
        $sheet->setCellValue('M'.$row, $invoice->getPaymentTerms() ?? '');
    }

    private function setMoneyCell(Worksheet $sheet, string $cell, ?int $cents, string $currency): void
    {
        if (null === $cents) {
            return;
        }

        $sheet->setCellValue($cell, $cents / 100);
        // Excel-friendly currency format: matches the locale's display.
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode(
            sprintf('#,##0.00 "%s"', $currency),
        );
    }
}
