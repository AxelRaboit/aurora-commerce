<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Service;

use Aurora\Core\Media\Service\MediaPathResolver;
use Aurora\Module\Billing\Invoice\Manager\InvoiceManagerInterface;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Ocr\Contract\DocTrClientInterface;
use Aurora\Module\Billing\Ocr\Contract\OllamaVisionClientInterface;
use Aurora\Module\Billing\Ocr\Entity\OcrJobInterface;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use Aurora\Module\Billing\Ocr\Manager\OcrJobManagerInterface;
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

    public function run(OcrJobInterface $job): void
    {
        $sourcePath = $this->mediaPathResolver->resolveAbsolutePath($job->getMedia());
        $this->logger->info('OCR pipeline starting', ['job_id' => $job->getId(), 'path' => $sourcePath]);

        $log = static function (string $level, string $message, array $ctx = []) use ($job): void {
            $job->appendLog($level, $message, $ctx);
        };

        // Stage 1 — docTR (text + layout)
        $media = $job->getMedia();
        $log('info', sprintf(
            'Fichier source : %s (%s, %s)',
            $media->getOriginalName(),
            $media->getMimeType(),
            $this->formatBytes($media->getSize()),
        ));

        $log('info', 'Étape 1/2 : extraction docTR en cours…');
        $this->logger->info('OCR stage 1/2: docTR extraction starting', ['job_id' => $job->getId(), 'path' => $sourcePath]);
        $this->jobManager->markExtracting($job);

        $doctrPayload = $this->doctr->extract($sourcePath);
        $this->jobManager->recordDoctrResult($job, $doctrPayload);

        $pageCount = count($doctrPayload['pages']);
        $textLen = mb_strlen($doctrPayload['text']);
        $log('info', sprintf('Étape 1/2 : docTR terminé — %d page(s), %d caractères extraits.', $pageCount, $textLen), [
            'pages' => $pageCount,
            'text_length' => $textLen,
        ]);
        if ($textLen > 0) {
            $preview = mb_substr(preg_replace('/\s+/', ' ', $doctrPayload['text']) ?? '', 0, 200);
            $log('info', 'Aperçu texte : «'.$preview.(mb_strlen($doctrPayload['text']) > 200 ? '…' : '').'»');
        }

        $this->logger->info('OCR stage 1/2: docTR done', ['job_id' => $job->getId(), 'pages' => $pageCount, 'text_length' => $textLen]);

        // Stage 2 — Ollama VLM (structured extraction on a renderable image)
        $model = $this->ollama->getModel();
        $log('info', sprintf('Étape 2/2 : analyse VLM en cours… (modèle : %s)', $model), ['model' => $model]);
        $this->logger->info('OCR stage 2/2: VLM extraction starting', ['job_id' => $job->getId(), 'model' => $model]);

        $imagePath = $this->documentRenderer->resolveImagePath($sourcePath, (int) $job->getId());
        $draft = $this->extractor->extract($imagePath, $doctrPayload['text']);
        $this->jobManager->recordVlmResult($job, $draft, $model);

        $pct = round($draft->confidence * 100);
        $log('info', sprintf('Étape 2/2 : VLM terminé — confiance %s%%, %d ligne(s) extraite(s).', $pct, count($draft->lines)), [
            'confidence' => $draft->confidence,
            'lines' => count($draft->lines),
        ]);

        // Résumé des champs extraits
        $extracted = array_filter([
            'Fournisseur' => $draft->supplierName,
            'N° TVA fournisseur' => $draft->supplierVatNumber,
            'Acheteur' => $draft->buyerName,
            'N° facture' => $draft->invoiceNumber,
            'Date émission' => $draft->issuedAt?->format('Y-m-d'),
            'Échéance' => $draft->dueAt?->format('Y-m-d'),
            'Total TTC' => null !== $draft->totalGrossCents ? number_format($draft->totalGrossCents / 100, 2, ',', ' ').' '.$draft->currency : null,
            'Total HT' => null !== $draft->totalNetCents ? number_format($draft->totalNetCents / 100, 2, ',', ' ').' '.$draft->currency : null,
        ]);
        foreach ($extracted as $label => $value) {
            $log('info', sprintf('  %s : %s', $label, $value));
        }

        if ([] === $extracted) {
            $log('warning', 'Aucun champ clé extrait — le document est peut-être illisible ou vide.');
        }

        if ([] !== $draft->uncertainFields) {
            $log('warning', 'Champs incertains : '.implode(', ', $draft->uncertainFields), ['fields' => $draft->uncertainFields]);
        }

        if (!$draft->isTrustworthy()) {
            $log('warning', 'Qualité insuffisante — facture marquée "Anomalie détectée".');
        }

        $this->logger->info('OCR stage 2/2: VLM done', ['job_id' => $job->getId(), 'confidence' => $draft->confidence]);

        // Guard: the job may have been deleted by the user during the long VLM call.
        if (null === $this->ocrJobRepository->find($job->getId())) {
            throw new UnrecoverableMessageHandlingException(sprintf('OcrJob %d was deleted during processing — aborting.', $job->getId()));
        }

        // Update existing editable invoice if one is already linked, otherwise create.
        $existing = $this->invoiceRepository->findOneBy(['ocrJob' => $job]);
        if (null !== $existing && $existing->getStatus()->isEditable()) {
            $log('info', sprintf('Mise à jour de la facture existante #%s.', $existing->getId()));
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
            'job_id' => $job->getId(),
            'status' => $job->getStatus()->value,
            'confidence' => $draft->confidence,
        ]);
    }

    private function formatBytes(?int $bytes): string
    {
        if (null === $bytes || $bytes < 0) {
            return '?';
        }

        foreach (['o', 'Ko', 'Mo', 'Go'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, 1).' '.$unit;
            }

            $bytes /= 1024;
        }

        return round($bytes, 1).' To';
    }
}
