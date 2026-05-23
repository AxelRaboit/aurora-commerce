<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\PersonalFinance\Import\Service;

use Aurora\Module\PersonalFinance\Import\Dto\PersonalFinanceImportPreview;
use Aurora\Module\PersonalFinance\Import\Service\PersonalFinanceImportServiceInterface;
use Aurora\Module\PersonalFinance\Transaction\Repository\PersonalFinanceTransactionRepository;
use Aurora\Tests\Integration\Module\PersonalFinance\PersonalFinanceTestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PersonalFinanceImportServiceTest extends PersonalFinanceTestCase
{
    private PersonalFinanceImportServiceInterface $importService;
    private PersonalFinanceTransactionRepository $transactionRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importService = $this->getService(PersonalFinanceImportServiceInterface::class);
        $this->transactionRepository = $this->getService(PersonalFinanceTransactionRepository::class);
    }

    public function testParseUploadRejectsFileWithWrongHeader(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W');

        $file = $this->createXlsx([
            ['Bogus', 'Bogus2', 'Bogus3', 'Bogus4', 'Bogus5', 'Bogus6'],
            ['2026-05-01', 'expense', '10.00', 'Food', 'Lunch', ''],
        ]);

        $preview = $this->importService->parseUpload($wallet, $file);

        self::assertTrue($preview->isBlocking());
        self::assertNotEmpty($preview->fatalErrors);
        self::assertEmpty($preview->rows);
    }

    public function testParseUploadFlagsInvalidRowsButKeepsValidOnes(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W');

        $file = $this->createXlsx([
            ['Date', 'Type', 'Amount', 'Category', 'Description', 'Tags'],
            ['2026-05-01', 'expense', '10.00', 'Food', 'Lunch', 'work'],
            ['not-a-date', 'expense', '20.00', 'Food', 'Bad date', ''],
            ['2026-05-03', 'mystery', '30.00', 'Food', 'Bad type', ''],
            ['2026-05-04', 'expense', 'abc', 'Food', 'Bad amount', ''],
            ['', '', '', '', '', ''], // empty row → silently skipped
            ['2026-05-05', 'income', '1500.00', 'Salary', 'Pay', ''],
        ]);

        $preview = $this->importService->parseUpload($wallet, $file);

        self::assertFalse($preview->isBlocking());
        self::assertSame(5, count($preview->rows), 'fully-empty row should be skipped');
        self::assertSame(2, $preview->validRowCount());
        self::assertSame(3, $preview->invalidRowCount());
        // Both categories are absent on the wallet → should be in newCategoryNames
        self::assertContains('Food', $preview->newCategoryNames);
        self::assertContains('Salary', $preview->newCategoryNames);
    }

    public function testProcessCreatesTransactionsAndAutoCreatesCategories(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W');

        // Pre-create Food so the test exercises both code paths (existing + auto-create).
        $this->createCategory($wallet, 'Food');

        $file = $this->createXlsx([
            ['Date', 'Type', 'Amount', 'Category', 'Description', 'Tags'],
            ['2026-05-01', 'expense', '10.00', 'Food', 'Lunch', 'work,team'],
            ['2026-05-02', 'income', '1500.00', 'Salary', 'Monthly pay', ''],
            ['bad-date', 'expense', '99.00', 'Food', 'Should skip', ''],
        ]);

        $preview = $this->importService->parseUpload($wallet, $file);
        $report = $this->importService->process($user, $wallet, $preview);

        self::assertSame(2, $report->createdCount);
        self::assertSame(1, $report->skippedCount);
        self::assertSame(['Salary'], $report->categoriesCreated, 'Food existed already');

        $transactions = $this->transactionRepository->findByWallet($wallet);
        self::assertCount(2, $transactions);
        // Order is DESC by date, so Salary first
        self::assertSame('1500.00', $transactions[0]->getAmount());
        self::assertSame('Salary', $transactions[0]->getCategory()?->getName());
        self::assertSame('10.00', $transactions[1]->getAmount());
        self::assertSame(['work', 'team'], $transactions[1]->getTags());
    }

    public function testProcessOnEmptyPreviewReturnsZeroCounts(): void
    {
        $user = $this->createTestUser();
        $wallet = $this->createWallet($user, 'W');

        $report = $this->importService->process($user, $wallet, new PersonalFinanceImportPreview([], [], []));

        self::assertSame(0, $report->createdCount);
        self::assertSame(0, $report->skippedCount);
        self::assertEmpty($report->categoriesCreated);
    }

    public function testBuildTemplateContentReturnsValidXlsx(): void
    {
        $content = $this->importService->buildTemplateContent();
        self::assertNotEmpty($content);
        // XLSX is a zip — starts with PK\x03\x04
        self::assertStringStartsWith("PK\x03\x04", $content);
    }

    /** @param list<list<string>> $rows */
    private function createXlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($rows as $i => $row) {
            $sheet->fromArray($row, null, 'A'.($i + 1));
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pf-import-').'.xlsx';
        new Xlsx($spreadsheet)->save($tmp);

        return new UploadedFile($tmp, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', test: true);
    }
}
