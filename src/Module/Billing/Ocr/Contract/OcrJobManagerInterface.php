<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Contract;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Billing\Ocr\DTO\InvoiceDraft;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface OcrJobManagerInterface
{
    /** Persist the upload as Media, create a queued OcrJob and dispatch it. */
    public function createFromUpload(UploadedFile $file, ?User $createdBy): OcrJob;

    /** Reset a (typically failed) job and re-dispatch it. */
    public function retry(OcrJob $job): void;

    /** Delete the job and its Media. Optionally also deletes the linked invoice (if deletable) and its supplier tiers. */
    public function delete(OcrJob $job, bool $deleteTiers = false): void;

    /** Mark the job as Extracting + record startedAt. */
    public function markExtracting(OcrJob $job): void;

    /**
     * Persist the docTR raw output and transition to Parsing.
     *
     * @param array<string, mixed> $rawDoctr
     */
    public function recordDoctrResult(OcrJob $job, array $rawDoctr): void;

    /** Persist the structured VLM extraction (raw + decoded + confidence + model). */
    public function recordVlmResult(OcrJob $job, InvoiceDraft $draft, string $modelUsed): void;

    /** Final transition: set status (Completed/NeedsReview) + finishedAt. */
    public function markFinished(OcrJob $job, OcrJobStatusEnum $status): void;

    /** Mark the job as Failed with an error message + finishedAt. */
    public function markFailed(OcrJob $job, string $error): void;
}
