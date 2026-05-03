<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Service;

use Aurora\Core\Media\Service\MediaPathResolver;
use Aurora\Module\Billing\Invoice\Contract\InvoiceManagerInterface;
use Aurora\Module\Billing\Ocr\Contract\DocTrClientInterface;
use Aurora\Module\Billing\Ocr\Contract\OcrJobManagerInterface;
use Aurora\Module\Billing\Ocr\Contract\OllamaVisionClientInterface;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use Psr\Log\LoggerInterface;

/**
 * Pure orchestrator for the two-stage OCR pipeline:
 *   1. docTR microservice — text + layout extraction
 *   2. Ollama vision model — structured invoice understanding
 *
 * Persistence + status transitions are delegated to OcrJobManager;
 * Invoice creation goes through InvoiceManager. This class only:
 *   - resolves file paths (via MediaPathResolver + OcrDocumentRenderer)
 *   - calls external HTTP services (docTR + Ollama via the extractor)
 *   - drives the state machine
 */
final readonly class OcrPipeline
{
    public function __construct(
        private DocTrClientInterface $doctr,
        private OllamaVisionClientInterface $ollama,
        private InvoiceExtractor $extractor,
        private OcrJobManagerInterface $jobManager,
        private InvoiceManagerInterface $invoiceManager,
        private MediaPathResolver $mediaPathResolver,
        private OcrDocumentRenderer $documentRenderer,
        private LoggerInterface $logger,
    ) {}

    public function run(OcrJob $job): void
    {
        $sourcePath = $this->mediaPathResolver->resolveAbsolutePath($job->getMedia());
        $this->logger->info('OCR pipeline starting', ['job_id' => $job->getId(), 'path' => $sourcePath]);

        // Stage 1 — docTR (text + layout)
        $this->jobManager->markExtracting($job);
        $doctrPayload = $this->doctr->extract($sourcePath);
        $this->jobManager->recordDoctrResult($job, $doctrPayload);

        // Stage 2 — Ollama VLM (structured extraction on a renderable image)
        $imagePath = $this->documentRenderer->resolveImagePath($sourcePath, (int) $job->getId());
        $draft = $this->extractor->extract($imagePath, $doctrPayload['text']);
        $this->jobManager->recordVlmResult($job, $draft, $this->ollama->getModel());

        // Hand off the draft to the invoice domain — creates a NeedsReview Invoice.
        $this->invoiceManager->createFromOcrDraft($draft, $job);

        $this->jobManager->markFinished(
            $job,
            $draft->isTrustworthy() ? OcrJobStatusEnum::Completed : OcrJobStatusEnum::NeedsReview,
        );

        $this->logger->info('OCR pipeline finished', [
            'job_id' => $job->getId(),
            'status' => $job->getStatus()->value,
            'confidence' => $draft->confidence,
        ]);
    }
}
