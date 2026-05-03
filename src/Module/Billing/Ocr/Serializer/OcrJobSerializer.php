<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Serializer;

use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use DateTimeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class OcrJobSerializer
{
    public function __construct(
        private TranslatorInterface $translator,
        private InvoiceRepository $invoiceRepository,
    ) {}

    public function serialize(OcrJob $job): array
    {
        $status = $job->getStatus();

        return [
            'id' => $job->getId(),
            'fileName' => $job->getMedia()->getOriginalName(),
            'mediaUrl' => $job->getMedia()->getPublicUrl(),
            'mediaMime' => $job->getMedia()->getMimeType(),
            'status' => $status->value,
            'statusLabel' => $this->translator->trans($status->getLabelKey()),
            'statusColor' => $status->getBadgeColor(),
            'isTerminal' => $status->isTerminal(),
            'progress' => $status->getProgress(),
            'modelUsed' => $job->getModelUsed(),
            'confidence' => $job->getConfidence(),
            'error' => $job->getError(),
            'invoiceId' => $this->invoiceRepository->findOneBy(['ocrJob' => $job])?->getId(),
            'logs' => $job->getLogs(),
            'createdAt' => $job->getCreatedAt()->format(DateTimeInterface::ATOM),
            'finishedAt' => $job->getFinishedAt()?->format(DateTimeInterface::ATOM),
        ];
    }
}
