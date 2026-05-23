<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Import\Service;

use Aurora\Module\PersonalFinance\Import\Dto\PersonalFinanceImportPreview;
use Aurora\Module\PersonalFinance\Import\Dto\PersonalFinanceImportReport;
use Aurora\Module\PersonalFinance\Wallet\Entity\PersonalFinanceWalletInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface PersonalFinanceImportServiceInterface
{
    /**
     * Parses an XLSX file. No DB writes — populates an in-RAM preview
     * with parsed rows, per-row errors, and the set of category names
     * that would have to be created on commit. UI uses this to let the
     * user audit before confirming.
     */
    public function parseUpload(
        PersonalFinanceWalletInterface $wallet,
        UploadedFile $file,
    ): PersonalFinanceImportPreview;

    /**
     * Processes a preview: creates the missing categories, inserts one
     * transaction per valid row via the Transaction Manager (audit +
     * domain events still fire). Invalid rows are reported, not raised
     * — partial imports are intentional so a single typo doesn't reject
     * a 200-row sheet.
     */
    public function process(
        CoreUserInterface $user,
        PersonalFinanceWalletInterface $wallet,
        PersonalFinanceImportPreview $preview,
    ): PersonalFinanceImportReport;

    /**
     * Streams an empty template XLSX so the user always uploads in the
     * correct shape (header row + 1 example row).
     */
    public function buildTemplateContent(): string;
}
