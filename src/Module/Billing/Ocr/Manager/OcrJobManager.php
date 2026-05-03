<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Media\Manager\MediaManager;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Billing\Ocr\Contract\OcrJobManagerInterface;
use Aurora\Module\Billing\Ocr\DTO\InvoiceDraft;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use Aurora\Module\Billing\Ocr\Message\ProcessOcrJobMessage;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsAlias(OcrJobManagerInterface::class)]
final readonly class OcrJobManager implements OcrJobManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MediaManager $mediaManager,
        private MessageBusInterface $bus,
        private AuditLogger $auditLogger,
    ) {}

    public function createFromUpload(UploadedFile $file, ?User $createdBy): OcrJob
    {
        $media = $this->mediaManager->upload($file);

        $job = new OcrJob();
        $job->setMedia($media);
        $job->setStatus(OcrJobStatusEnum::Queued);
        $job->setCreatedBy($createdBy);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->bus->dispatch(new ProcessOcrJobMessage($job->getId()));

        $this->auditLogger->log('billing', 'ocr.job.created', 'OcrJob', $job->getId(), [
            'mediaId' => $media->getId(),
            'fileName' => $media->getOriginalName(),
        ]);

        return $job;
    }

    public function retry(OcrJob $job): void
    {
        $job->setStatus(OcrJobStatusEnum::Queued);
        $job->setError(null);
        $job->setStartedAt(null);
        $job->setFinishedAt(null);

        $this->entityManager->flush();

        $this->bus->dispatch(new ProcessOcrJobMessage($job->getId()));

        $this->auditLogger->log('billing', 'ocr.job.retried', 'OcrJob', $job->getId());
    }

    public function delete(OcrJob $job): void
    {
        $id = $job->getId();

        $this->entityManager->remove($job);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'ocr.job.deleted', 'OcrJob', $id);
    }

    public function markExtracting(OcrJob $job): void
    {
        $job->setStartedAt(new DateTimeImmutable());
        $job->setStatus(OcrJobStatusEnum::Extracting);

        $this->entityManager->flush();
    }

    public function recordDoctrResult(OcrJob $job, array $rawDoctr): void
    {
        $job->setRawDoctr($rawDoctr);
        $job->setStatus(OcrJobStatusEnum::Parsing);

        $this->entityManager->flush();
    }

    public function recordVlmResult(OcrJob $job, InvoiceDraft $draft, string $modelUsed): void
    {
        $payload = $draft->toArray();
        $job->setRawVlm($payload);
        $job->setExtracted($payload);
        $job->setConfidence($draft->confidence);
        $job->setModelUsed($modelUsed);

        $this->entityManager->flush();
    }

    public function markFinished(OcrJob $job, OcrJobStatusEnum $status): void
    {
        $job->setStatus($status);
        $job->setFinishedAt(new DateTimeImmutable());

        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'ocr.job.finished', 'OcrJob', $job->getId(), [
            'status' => $status->value,
            'confidence' => $job->getConfidence(),
        ]);
    }

    public function markFailed(OcrJob $job, string $error): void
    {
        $job->setStatus(OcrJobStatusEnum::Failed);
        $job->setError($error);
        $job->setFinishedAt(new DateTimeImmutable());

        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'ocr.job.failed', 'OcrJob', $job->getId(), [
            'error' => mb_substr($error, 0, 200),
        ]);
    }
}
