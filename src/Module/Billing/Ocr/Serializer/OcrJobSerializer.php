<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Serializer;

use Aurora\Core\Media\Library\Service\MediaUrlGenerator;
use Aurora\Module\Billing\Invoice\Entity\TiersInterface;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Ocr\Entity\OcrJobInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(OcrJobSerializerInterface::class)]
class OcrJobSerializer implements OcrJobSerializerInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly InvoiceRepository $invoiceRepository,
        protected readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {}

    public function serialize(OcrJobInterface $job): array
    {
        $status = $job->getStatus();

        return [
            'id' => $job->getId(),
            'fileName' => $job->getMedia()->getOriginalName(),
            'mediaUrl' => $this->mediaUrlGenerator->publicUrl($job->getMedia()),
            'mediaMime' => $job->getMedia()->getMimeType(),
            'status' => $status->value,
            'statusLabel' => $this->translator->trans($status->getLabelKey()),
            'statusColor' => $status->getBadgeColor(),
            'isTerminal' => $status->isTerminal(),
            'progress' => $status->getProgress(),
            'modelUsed' => $job->getModelUsed(),
            'confidence' => $job->getConfidence(),
            'error' => $job->getError(),
            'invoiceId' => ($invoice = $this->invoiceRepository->findOneBy(['ocrJob' => $job]))?->getId(),
            'invoiceStatus' => $invoice?->getStatus()->value,
            'invoiceCanValidate' => $invoice?->getStatus()->isEditable() ?? false,
            'invoiceCanDeleteTiers' => $invoice?->getStatus()->isDeletable()
                && $invoice->getTiers() instanceof TiersInterface
                && 1 === $this->invoiceRepository->countForTiers($invoice->getTiers()->getId()),
            'invoiceSupplierName' => $invoice?->getTiers()?->getName(),
            'logs' => $job->getLogs(),
            'createdAt' => $job->getCreatedAt()->format(DateTimeInterface::ATOM),
            'finishedAt' => $job->getFinishedAt()?->format(DateTimeInterface::ATOM),
        ];
    }
}
