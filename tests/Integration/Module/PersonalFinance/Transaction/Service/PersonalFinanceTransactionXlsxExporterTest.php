<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Transaction\Service;

use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Service\PersonalFinanceTransactionXlsxExporter;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PersonalFinanceTransactionXlsxExporterTest extends PersonalFinanceTestCase
{
    private PersonalFinanceTransactionXlsxExporter $exporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = $this->getService(PersonalFinanceTransactionXlsxExporter::class);
    }

    public function testBuildResponseHasXlsxHeadersAndAttachmentFilename(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'My Wallet', '0.00');

        $response = $this->exporter->buildResponse($wallet, []);

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type'),
        );
        $disposition = (string) $response->headers->get('Content-Disposition');
        self::assertStringContainsString('attachment;', $disposition);
        self::assertStringContainsString('personal-finance-transactions-my-wallet-', $disposition);
        self::assertStringEndsWith('.xlsx"', $disposition);
    }

    public function testBuildResponseStreamsRowsWithSignedAmounts(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'Daily', '0.00');
        $cat = $this->createCategory($wallet, 'Food');

        $income = $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Income, '1500.00', new DateTimeImmutable('2026-05-01'), 'Income row');
        $expense = $this->createTransaction($wallet, $cat, PersonalFinanceTransactionTypeEnum::Expense, '42.50', new DateTimeImmutable('2026-05-10'), 'Expense row');

        $tmp = tempnam(sys_get_temp_dir(), 'pf-xlsx-').'.xlsx';

        try {
            $response = $this->exporter->buildResponse($wallet, [$income, $expense]);
            ob_start();
            $response->sendContent();
            file_put_contents($tmp, (string) ob_get_clean());

            $spreadsheet = IOFactory::load($tmp);
            $sheet = $spreadsheet->getActiveSheet();

            self::assertSame('Date', $sheet->getCell('B1')->getValue());
            self::assertSame('Amount', $sheet->getCell('D1')->getValue());

            // Two rows in HEADERS order, signed by type
            self::assertSame('income', $sheet->getCell('C2')->getValue());
            self::assertSame(1500.0, (float) $sheet->getCell('D2')->getValue());
            self::assertSame('expense', $sheet->getCell('C3')->getValue());
            self::assertSame(-42.5, (float) $sheet->getCell('D3')->getValue());
            self::assertSame('Income row', $sheet->getCell('F2')->getValue());
        } finally {
            @unlink($tmp);
        }
    }
}
