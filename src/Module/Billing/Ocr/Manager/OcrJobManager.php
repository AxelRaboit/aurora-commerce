<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Media\Library\Enum\StorageAreaEnum;
use Aurora\Module\Media\Library\Manager\MediaManagerInterface;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\Platform\User\Entity\User;
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
        protected readonly MediaManagerInterface $mediaManager,
        protected readonly MessageBusInterface $bus,
        protected readonly AuditLogger $auditLogger,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
        protected readonly InvoiceRepository $invoiceRepository,
        protected readonly TiersManagerInterface $tiersManager,
    ) {}

    public function createFromUpload(UploadedFile $file, ?User $createdBy): OcrJobInterface
    {
        $media = $this->mediaManager->upload($file, null, StorageAreaEnum::Ocr);

        $job = $this->createOcrJob();
        $job->setMedia($media);
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
            'mediaId' => $media->getId(),
            'fileName' => $media->getOriginalName(),
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
        $media = $job->getMedia();
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
        $this->entityManager->flush();

        // Delete the uploaded file and its Media record — it was created solely
        // for this OCR job and has no other owner once the job is gone.
        // Invoice.document is SET NULL by the DB cascade if it referenced the same media.
        $this->mediaManager->delete($media);

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
