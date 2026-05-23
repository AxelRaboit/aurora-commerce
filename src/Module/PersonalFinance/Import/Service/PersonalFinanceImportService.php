<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Import\Service;

use Aurora\Module\PersonalFinance\Category\Dto\PersonalFinanceCategoryInput;
use Aurora\Module\PersonalFinance\Category\Manager\PersonalFinanceCategoryManagerInterface;
use Aurora\Module\PersonalFinance\Category\Repository\PersonalFinanceCategoryRepository;
use Aurora\Module\PersonalFinance\Import\Dto\PersonalFinanceImportPreview;
use Aurora\Module\PersonalFinance\Import\Dto\PersonalFinanceImportReport;
use Aurora\Module\PersonalFinance\Import\Dto\PersonalFinanceImportRow;
use Aurora\Module\PersonalFinance\Transaction\Dto\PersonalFinanceTransactionInput;
use Aurora\Module\PersonalFinance\Transaction\Enum\PersonalFinanceTransactionTypeEnum;
use Aurora\Module\PersonalFinance\Transaction\Manager\PersonalFinanceTransactionManagerInterface;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

/**
 * Bulk-import historical transactions from an XLSX file. MVP scope:
 * - fixed column layout (Date / Type / Amount / Category / Description / Tags)
 * - per-row validation with partial-success processing
 * - auto-creates missing categories on the target wallet
 * - max 5000 rows per upload (memory + UX safety)
 *
 * Non-`final` + `readonly` props so a client can subclass to relax the
 * row cap, swap the header layout, or change the category-creation
 * policy (e.g. require pre-existence).
 */
#[AsAlias(PersonalFinanceImportServiceInterface::class)]
readonly class PersonalFinanceImportService implements PersonalFinanceImportServiceInterface
{
    protected const int MAX_ROWS = 5000;

    protected const array HEADER_LAYOUT = [
        'A' => 'date',
        'B' => 'type',
        'C' => 'amount',
        'D' => 'category',
        'E' => 'description',
        'F' => 'tags',
    ];

    protected const array EXPECTED_HEADERS = ['date', 'type', 'amount', 'category', 'description', 'tags'];

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected PersonalFinanceTransactionManagerInterface $transactionManager,
        protected PersonalFinanceCategoryManagerInterface $categoryManager,
        protected PersonalFinanceCategoryRepository $categoryRepository,
    ) {}

    public function parseUpload(PersonalFinanceWalletInterface $wallet, UploadedFile $file): PersonalFinanceImportPreview
    {
        $fatal = [];

        try {
            $reader = IOFactory::createReaderForFile($file->getPathname());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getPathname());
        } catch (Throwable $throwable) {
            return new PersonalFinanceImportPreview([], [], [sprintf('Could not read uploaded file: %s', $throwable->getMessage())]);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        if ($highestRow > self::MAX_ROWS + 1) {
            $fatal[] = sprintf('Too many rows: %d (max %d).', $highestRow - 1, self::MAX_ROWS);
        }

        $headerErr = $this->validateHeader($sheet);
        if (null !== $headerErr) {
            $fatal[] = $headerErr;
        }

        if ([] !== $fatal) {
            return new PersonalFinanceImportPreview([], [], $fatal);
        }

        $existingNames = array_map(
            static fn ($c): string => mb_strtolower($c->getName()),
            $this->categoryRepository->findUserCategoriesByWallet($wallet),
        );

        $rows = [];
        $newCategoriesSeen = [];

        for ($r = 2; $r <= $highestRow; ++$r) {
            $row = $this->parseRow($sheet, $r);
            if (!$row instanceof PersonalFinanceImportRow) {
                continue;
            }

            $rows[] = $row;
            if (null !== $row->categoryName && $row->isValid() && !in_array(mb_strtolower($row->categoryName), $existingNames, true)
                && !in_array($row->categoryName, $newCategoriesSeen, true)) {
                $newCategoriesSeen[] = $row->categoryName;
            }
        }

        return new PersonalFinanceImportPreview($rows, $newCategoriesSeen, []);
    }

    public function process(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceImportPreview $preview,
    ): PersonalFinanceImportReport {
        $categoryCache = [];
        foreach ($this->categoryRepository->findUserCategoriesByWallet($wallet) as $cat) {
            $categoryCache[mb_strtolower($cat->getName())] = $cat;
        }

        $created = 0;
        $skipped = 0;
        $skippedRows = [];
        $categoriesCreated = [];

        foreach ($preview->rows as $row) {
            if (!$row->isValid()) {
                ++$skipped;
                $skippedRows[] = sprintf('row %d: %s', $row->rowNumber, implode(', ', $row->errors));
                continue;
            }

            $categoryId = null;
            if (null !== $row->categoryName) {
                $key = mb_strtolower($row->categoryName);
                if (!isset($categoryCache[$key])) {
                    $category = $this->categoryManager->create(
                        $user,
                        $wallet,
                        new PersonalFinanceCategoryInput(name: $row->categoryName),
                    );
                    $categoryCache[$key] = $category;
                    $categoriesCreated[] = $row->categoryName;
                }

                $categoryId = $categoryCache[$key]->getId();
            }

            $input = new PersonalFinanceTransactionInput(
                type: $row->type ?? PersonalFinanceTransactionTypeEnum::Expense,
                amount: $row->amount ?? '0.00',
                date: $row->date,
                description: $row->description,
                categoryId: $categoryId,
                tags: $row->tags,
            );

            $this->transactionManager->create($user, $wallet, $input);
            ++$created;
        }

        return new PersonalFinanceImportReport(
            createdCount: $created,
            skippedCount: $skipped,
            categoriesCreated: $categoriesCreated,
            skippedRows: $skippedRows,
        );
    }

    public function buildTemplateContent(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Transactions');

        $headers = array_map(ucfirst(...), self::EXPECTED_HEADERS);
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Example row to make the format unambiguous (1 expense, 1 income).
        $sheet->fromArray(
            [new DateTimeImmutable('today')->format('Y-m-d'), 'expense', '42.50', 'Food', 'Lunch with team', 'work,offsite'],
            null,
            'A2',
        );
        $sheet->fromArray(
            [new DateTimeImmutable('today')->format('Y-m-d'), 'income', '1500.00', 'Salary', 'Monthly salary', ''],
            null,
            'A3',
        );

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    protected function validateHeader($sheet): ?string
    {
        $found = [];
        foreach (self::HEADER_LAYOUT as $col => $expected) {
            $value = mb_strtolower(mb_trim((string) $sheet->getCell($col.'1')->getValue()));
            $found[] = $value;
            if ($value !== $expected) {
                return sprintf(
                    'Unexpected header at %s1 — expected "%s", found "%s". Re-download the template.',
                    $col,
                    $expected,
                    $value,
                );
            }
        }

        return null;
    }

    protected function parseRow($sheet, int $rowNumber): ?PersonalFinanceImportRow
    {
        $rawValues = [];
        foreach (self::HEADER_LAYOUT as $col => $field) {
            $rawValues[$field] = $sheet->getCell($col.$rowNumber)->getValue();
        }

        // Skip fully-empty rows silently
        if ([] === array_filter($rawValues, static fn ($v): bool => null !== $v && '' !== (string) $v)) {
            return null;
        }

        $errors = [];

        $date = $this->parseDate($rawValues['date'], $errors);
        $type = $this->parseType($rawValues['type'], $errors);
        $amount = $this->parseAmount($rawValues['amount'], $errors);

        $categoryName = $this->parseString($rawValues['category']);
        $description = $this->parseString($rawValues['description']);
        $tags = $this->parseTags($rawValues['tags']);

        return new PersonalFinanceImportRow(
            rowNumber: $rowNumber,
            date: $date,
            type: $type,
            amount: $amount,
            categoryName: $categoryName,
            description: $description,
            tags: $tags,
            errors: $errors,
            rawValues: $rawValues,
        );
    }

    /** @param list<string> $errors */
    protected function parseDate(mixed $value, array &$errors): ?DateTimeImmutable
    {
        if (null === $value || '' === (string) $value) {
            $errors[] = 'date is required';

            return null;
        }

        // Excel serial date (numeric) — convert via PhpSpreadsheet helper
        if (is_numeric($value)) {
            try {
                return DateTimeImmutable::createFromMutable(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (Throwable) {
                $errors[] = 'date is not a valid Excel serial';

                return null;
            }
        }

        // String — accept ISO YYYY-MM-DD primarily, fallback to DateTimeImmutable parser
        $stringValue = mb_trim((string) $value);
        try {
            return new DateTimeImmutable($stringValue);
        } catch (Throwable) {
            $errors[] = sprintf('date "%s" is not parseable (use YYYY-MM-DD)', $stringValue);

            return null;
        }
    }

    /** @param list<string> $errors */
    protected function parseType(mixed $value, array &$errors): ?PersonalFinanceTransactionTypeEnum
    {
        $stringValue = mb_strtolower(mb_trim((string) $value));
        if ('' === $stringValue) {
            $errors[] = 'type is required';

            return null;
        }

        $resolved = PersonalFinanceTransactionTypeEnum::tryFrom($stringValue);
        if (null === $resolved) {
            $errors[] = sprintf('type "%s" must be one of: income, expense', $stringValue);

            return null;
        }

        return $resolved;
    }

    /** @param list<string> $errors */
    protected function parseAmount(mixed $value, array &$errors): ?string
    {
        if (null === $value || '' === (string) $value) {
            $errors[] = 'amount is required';

            return null;
        }

        $normalised = str_replace(',', '.', mb_trim((string) $value));
        if (1 !== preg_match('/^\d{1,8}(\.\d{1,2})?$/', $normalised)) {
            $errors[] = sprintf('amount "%s" is not a valid positive decimal (max 2 fractional digits)', $value);

            return null;
        }

        // Normalise to the bcmath shape used by the DB column (`decimal(10,2)`)
        // so the stored value is consistent regardless of whether Excel wrote
        // 1500 or 1500.00 — keeps cross-row reporting + sums clean.
        return bcadd('0', $normalised, 2);
    }

    protected function parseString(mixed $value): ?string
    {
        $stringValue = mb_trim((string) ($value ?? ''));

        return '' === $stringValue ? null : $stringValue;
    }

    /** @return list<string> */
    protected function parseTags(mixed $value): array
    {
        $stringValue = mb_trim((string) ($value ?? ''));
        if ('' === $stringValue) {
            return [];
        }

        $parts = preg_split('/[,;]/', $stringValue) ?: [];
        $tags = [];
        foreach ($parts as $part) {
            $clean = mb_trim($part);
            if ('' !== $clean && !in_array($clean, $tags, true)) {
                $tags[] = $clean;
            }
        }

        return $tags;
    }
}
