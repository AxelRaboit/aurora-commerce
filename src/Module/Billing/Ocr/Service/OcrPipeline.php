<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Service;

use Aurora\Core\Media\Service\MediaPathResolver;
use Aurora\Module\Billing\Invoice\Contract\InvoiceManagerInterface;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Ocr\Contract\DocTrClientInterface;
use Aurora\Module\Billing\Ocr\Contract\OcrJobManagerInterface;
use Aurora\Module\Billing\Ocr\Contract\OllamaVisionClientInterface;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use Aurora\Module\Billing\Ocr\Repository\OcrJobRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

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
        private InvoiceRepository $invoiceRepository,
        private OcrJobRepository $ocrJobRepository,
        private MediaPathResolver $mediaPathResolver,
        private OcrDocumentRenderer $documentRenderer,
        private LoggerInterface $logger,
    ) {}

    public function run(OcrJob $job): void
    {
        $sourcePath = $this->mediaPathResolver->resolveAbsolutePath($job->getMedia());
        $this->logger->info('OCR pipeline starting', ['job_id' => $job->getId(), 'path' => $sourcePath]);

        $log = static function (string $level, string $message, array $ctx = []) use ($job): void {
            $job->appendLog($level, $message, $ctx);
        };

        // Stage 1 — docTR (text + layout)
        $log('info', 'Étape 1/2 : extraction docTR en cours…');
        $this->logger->info('OCR stage 1/2: docTR extraction starting', ['job_id' => $job->getId()]);
        $this->jobManager->markExtracting($job);

        $doctrPayload = $this->doctr->extract($sourcePath);
        $this->jobManager->recordDoctrResult($job, $doctrPayload);

        $textLen = mb_strlen($doctrPayload['text'] ?? '');
        $log('info', "Étape 1/2 : docTR terminé — {$textLen} caractères extraits.", ['text_length' => $textLen]);
        $this->logger->info('OCR stage 1/2: docTR done', ['job_id' => $job->getId(), 'text_length' => $textLen]);

        // Stage 2 — Ollama VLM (structured extraction on a renderable image)
        $model = $this->ollama->getModel();
        $log('info', "Étape 2/2 : analyse VLM en cours… (modèle : {$model})", ['model' => $model]);
        $this->logger->info('OCR stage 2/2: VLM extraction starting', ['job_id' => $job->getId(), 'model' => $model]);

        $imagePath = $this->documentRenderer->resolveImagePath($sourcePath, (int) $job->getId());
        $draft = $this->extractor->extract($imagePath, $doctrPayload['text']);
        $this->jobManager->recordVlmResult($job, $draft, $model);

        $pct = round($draft->confidence * 100);
        $log('info', "Étape 2/2 : VLM terminé — confiance {$pct}%, ".count($draft->lines)." ligne(s) extraite(s).", [
            'confidence' => $draft->confidence,
            'lines'      => count($draft->lines),
        ]);
        if ($draft->uncertainFields !== []) {
            $log('warning', 'Champs incertains signalés : '.implode(', ', $draft->uncertainFields), ['fields' => $draft->uncertainFields]);
        }
        if (!$draft->isTrustworthy()) {
            $log('warning', 'Qualité insuffisante — facture marquée "Anomalie détectée".');
        }
        $this->logger->info('OCR stage 2/2: VLM done', ['job_id' => $job->getId(), 'confidence' => $draft->confidence]);

        // Guard: the job may have been deleted by the user during the long VLM call.
        if ($this->ocrJobRepository->find($job->getId()) === null) {
            throw new UnrecoverableMessageHandlingException(
                sprintf('OcrJob %d was deleted during processing — aborting.', $job->getId()),
            );
        }

        // Update existing editable invoice if one is already linked, otherwise create.
        $existing = $this->invoiceRepository->findOneBy(['ocrJob' => $job]);
        if ($existing !== null && $existing->getStatus()->isEditable()) {
            $log('info', "Mise à jour de la facture existante #{$existing->getId()}.");
            $this->invoiceManager->updateFromOcrDraft($existing, $draft, $job);
        } else {
            $log('info', 'Création de la facture depuis le brouillon OCR.');
            $this->invoiceManager->createFromOcrDraft($draft, $job);
        }

        $this->jobManager->markFinished(
            $job,
            $draft->isTrustworthy() ? OcrJobStatusEnum::Completed : OcrJobStatusEnum::NeedsReview,
        );

        $log('info', 'Pipeline terminé — statut : '.$job->getStatus()->value.'.');
        $this->logger->info('OCR pipeline finished', [
            'job_id'     => $job->getId(),
            'status'     => $job->getStatus()->value,
            'confidence' => $draft->confidence,
        ]);
    }
}
