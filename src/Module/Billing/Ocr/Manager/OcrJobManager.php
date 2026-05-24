<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Billing\Invoice\Entity\InvoiceInterface;
use Aurora\Module\Billing\Invoice\Entity\TiersInterface;
use Aurora\Module\Billing\Invoice\Manager\TiersManagerInterface;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Ocr\Dto\InvoiceDraft;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Billing\Ocr\Entity\OcrJobInterface;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use Aurora\Module\Billing\Ocr\Message\ProcessOcrJobMessage;
use Aurora\Module\Billing\Setting\BillingSettingEnum;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Ged\Document\Entity\Document;
use Aurora\Module\Ged\Document\Service\GedDocumentUploader;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Aurora\Module\Platform\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsAlias(OcrJobManagerInterface::class)]
class OcrJobManager implements OcrJobManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly GedDocumentUploader $documentUploader,
        protected readonly MessageBusInterface $bus,
        protected readonly AuditLogger $auditLogger,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
        protected readonly InvoiceRepository $invoiceRepository,
        protected readonly TiersManagerInterface $tiersManager,
    ) {}

    public function createFromUpload(UploadedFile $file, ?User $createdBy): OcrJobInterface
    {
        // Create a GED Document directly — the same Document will be
        // referenced by the Invoice that gets produced from this OCR draft
        // later. Single file, single storage, single audit trail. Cf.
        // pattern_self_owned_storage + the welding W3 strategy.
        $meta = $this->documentUploader->upload($file);

        $document = new Document();
        $document->setTitle($meta['originalName'])
            ->setStatus(DocumentStatusEnum::Draft)
            ->setFilePath($meta['filePath'])
            ->setFileName($meta['fileName'])
            ->setOriginalName($meta['originalName'])
            ->setMimeType($meta['mimeType'])
            ->setSize($meta['size']);
        $this->entityManager->persist($document);

        $job = $this->createOcrJob();
        $job->setDocument($document);
        $job->setStatus(OcrJobStatusEnum::Queued);
        $job->setCreatedBy($createdBy);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $prefix = $this->settingRepository->getOrDefault(BillingSettingEnum::OcrJobPrefix);
        $job->setReference($this->sequenceGenerator->next($prefix));
        $this->entityManager->flush();

        $this->bus->dispatch(new ProcessOcrJobMessage($job->getId()));

        $this->auditLogger->log('billing', 'ocr.job.created', 'OcrJob', $job->getId(), [
            ...$this->auditPayload($job),
            'documentId' => $document->getId(),
            'fileName' => $document->getOriginalName(),
        ]);

        return $job;
    }

    public function retry(OcrJobInterface $job): void
    {
        $job->setStatus(OcrJobStatusEnum::Queued);
        $job->setError(null);
        $job->setStartedAt(null);
        $job->setFinishedAt(null);

        $this->entityManager->flush();

        $this->bus->dispatch(new ProcessOcrJobMessage($job->getId()));

        $this->auditLogger->log('billing', 'ocr.job.retried', 'OcrJob', $job->getId(), $this->auditPayload($job));
    }

    public function delete(OcrJobInterface $job, bool $deleteTiers = false): void
    {
        $id = $job->getId();
        $payload = $this->auditPayload($job);
        $document = $job->getDocument();
        $tiers = null;
        $invoiceId = null;
        $invoiceNumber = null;

        $invoice = $this->invoiceRepository->findOneBy(['ocrJob' => $job]);
        if ($invoice instanceof InvoiceInterface && $invoice->getStatus()->isDeletable()) {
            $tiers = $deleteTiers ? $invoice->getTiers() : null;
            $invoiceId = $invoice->getId();
            $invoiceNumber = $invoice->getNumber();
            $this->entityManager->remove($invoice);
            $this->entityManager->flush();
        }

        if ($tiers instanceof TiersInterface) {
            $this->tiersManager->delete($tiers);
        }

        $this->entityManager->remove($job);
        // Cascade-remove the GED Document this OcrJob owns. The Invoice
        // referenced the same Document — already removed above if there
        // was one. The on-disk file lives in GED storage and stays
        // (intentional: orphan files are safer than dangling DB rows).
        $this->entityManager->remove($document);
        $this->entityManager->flush();

        if (null !== $invoiceId) {
            $this->auditLogger->log('billing', 'invoice.deleted', 'Invoice', $invoiceId, [
                'number' => $invoiceNumber,
                'tiersDeleted' => $tiers instanceof TiersInterface,
            ]);
        }

        $this->auditLogger->log('billing', 'ocr.job.deleted', 'OcrJob', $id, $payload);
    }

    public function markExtracting(OcrJobInterface $job): void
    {
        $job->setStartedAt(new DateTimeImmutable());
        $job->setStatus(OcrJobStatusEnum::Extracting);

        $this->entityManager->flush();
    }

    public function recordDoctrResult(OcrJobInterface $job, array $rawDoctr): void
    {
        $job->setRawDoctr($rawDoctr);
        $job->setStatus(OcrJobStatusEnum::Parsing);

        $this->entityManager->flush();
    }

    public function recordVlmResult(OcrJobInterface $job, InvoiceDraft $draft, string $modelUsed): void
    {
        $payload = $draft->toArray();
        $job->setRawVlm($payload);
        $job->setExtracted($payload);
        $job->setConfidence($draft->confidence);
        $job->setModelUsed($modelUsed);

        $this->entityManager->flush();
    }

    public function markFinished(OcrJobInterface $job, OcrJobStatusEnum $status): void
    {
        $job->setStatus($status);
        $job->setFinishedAt(new DateTimeImmutable());

        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'ocr.job.finished', 'OcrJob', $job->getId(), [
            ...$this->auditPayload($job),
            'status' => $status->value,
            'confidence' => $job->getConfidence(),
        ]);
    }

    public function markFailed(OcrJobInterface $job, string $error): void
    {
        $job->setStatus(OcrJobStatusEnum::Failed);
        $job->setError($error);
        $job->setFinishedAt(new DateTimeImmutable());

        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'ocr.job.failed', 'OcrJob', $job->getId(), [
            ...$this->auditPayload($job),
            'error' => mb_substr($error, 0, 200),
        ]);
    }

    protected function createOcrJob(): OcrJobInterface
    {
        return new OcrJob();
    }

    /**
     * Base payload for every OcrJob audit entry. Override to add custom fields.
     *
     * Note: OcrJob's lifecycle is exclusively domain events (created,
     * retried, deleted, finished:status, failed:error). The standard
     * `auditCreated/Updated/Deleted` triplet does not apply.
     */
    protected function auditPayload(OcrJobInterface $job): array
    {
        return ['reference' => $job->getReference()];
    }
}
