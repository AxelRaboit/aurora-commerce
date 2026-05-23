<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Attachment\Service;

use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface PersonalFinanceTransactionAttachmentServiceInterface
{
    /**
     * Replace (or add) the attachment on the given transaction. If an
     * existing attachment is present, it is deleted from disk first
     * (1 file per transaction invariant).
     *
     * Mutates and flushes the transaction.
     */
    public function attach(PersonalFinanceTransactionInterface $transaction, UploadedFile $file): void;

    /**
     * Remove the attachment from the transaction (file on disk + DB
     * columns set to null). No-op if no attachment is present.
     */
    public function detach(PersonalFinanceTransactionInterface $transaction): void;

    /**
     * Build a BinaryFileResponse for downloading the attachment. The
     * caller is responsible for ACL gating (typically
     * PersonalFinanceWalletVoter::VIEW on the wallet).
     */
    public function serve(PersonalFinanceTransactionInterface $transaction): BinaryFileResponse;

    /**
     * Remove the entire `{transactionId}/` directory from disk. Called
     * by the TransactionManager just after a transaction is deleted to
     * cleanup the orphan dir.
     */
    public function purgeDirectory(int $transactionId): void;
}
