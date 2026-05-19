<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Manager;

use Aurora\Module\Billing\Ocr\Dto\InvoiceDraft;
use Aurora\Module\Billing\Ocr\Entity\OcrJobInterface;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use Aurora\Module\Platform\User\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface OcrJobManagerInterface
{
    /** Persist the upload as Media, create a queued OcrJob and dispatch it. */
    public function createFromUpload(UploadedFile $file, ?User $createdBy): OcrJobInterface;

    /** Reset a (typically failed) job and re-dispatch it. */
    public function retry(OcrJobInterface $job): void;

    /** Delete the job and its Media. Optionally also deletes the linked invoice (if deletable) and its supplier tiers. */
    public function delete(OcrJobInterface $job, bool $deleteTiers = false): void;

    /** Mark the job as Extracting + record startedAt. */
    public function markExtracting(OcrJobInterface $job): void;

    /**
     * Persist the docTR raw output and transition to Parsing.
     *
     * @param array<string, mixed> $rawDoctr
     */
    public function recordDoctrResult(OcrJobInterface $job, array $rawDoctr): void;

    /** Persist the structured VLM extraction (raw + decoded + confidence + model). */
    public function recordVlmResult(OcrJobInterface $job, InvoiceDraft $draft, string $modelUsed): void;

    /** Final transition: set status (Completed/NeedsReview) + finishedAt. */
    public function markFinished(OcrJobInterface $job, OcrJobStatusEnum $status): void;

    /** Mark the job as Failed with an error message + finishedAt. */
    public function markFailed(OcrJobInterface $job, string $error): void;
}
