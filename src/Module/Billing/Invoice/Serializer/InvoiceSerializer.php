<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Serializer;

use Aurora\Module\Billing\Invoice\Entity\InvoiceInterface;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLineInterface;
use Aurora\Module\Billing\Invoice\Entity\TiersInterface;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Ocr\Entity\OcrJobInterface;
use Aurora\Module\Media\Library\Entity\MediaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(InvoiceSerializerInterface::class)]
class InvoiceSerializer implements InvoiceSerializerInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly TiersSerializer $tiersSerializer,
        private readonly InvoiceRepository $invoiceRepository,
    ) {}

    public function serialize(InvoiceInterface $invoice): array
    {
        $status = $invoice->getStatus();

        return [
            'id' => $invoice->getId(),
            'number' => $invoice->getNumber(),
            'supplierNumber' => $invoice->getSupplierNumber(),
            'status' => $status->value,
            'statusLabel' => $this->translator->trans($status->getLabelKey()),
            'statusColor' => $status->getBadgeColor(),
            'isCancelled' => $invoice->isCancelled(),
            'isCreditNote' => InvoiceStatusEnum::CreditNote === $invoice->getStatus(),
            'isDeletable' => $invoice->getStatus()->isDeletable(),
            'supplier' => $invoice->getTiers() instanceof TiersInterface ? [
                'id' => $invoice->getTiers()->getId(),
                'name' => $invoice->getTiers()->getName(),
            ] : null,
            'issuedAt' => $invoice->getIssuedAt()?->format('Y-m-d'),
            'dueAt' => $invoice->getDueAt()?->format('Y-m-d'),
            'currency' => $invoice->getCurrency()->value,
            'subtotalCents' => $invoice->getSubtotalCents(),
            'totalNetCents' => $invoice->getTotalNetCents(),
            'totalVatCents' => $invoice->getTotalVatCents(),
            'totalGrossCents' => $invoice->getTotalGrossCents(),
            'discountCents' => $invoice->getDiscountCents(),
            'freightCents' => $invoice->getFreightCents(),
            'insuranceCents' => $invoice->getInsuranceCents(),
            'discountRateBp' => $invoice->getDiscountRateBp(),
            'reference' => $invoice->getReference(),
            'project' => $invoice->getProject(),
            'incoterms' => $invoice->getIncoterms(),
            'deliveryDate' => $invoice->getDeliveryDate()?->format('Y-m-d'),
            'reverseCharge' => $invoice->getReverseCharge(),
            'bankDetails' => $invoice->getBankDetails(),
        ];
    }

    /**
     * Full payload used by the review screen — adds supplier full info, lines,
     * payment terms, OCR job traceability and the source document URL.
     */
    public function serializeDetail(InvoiceInterface $invoice): array
    {
        $base = $this->serialize($invoice);

        $document = $invoice->getDocument();
        $job = $invoice->getOcrJob();

        return [
            ...$base,
            'purchaseOrderRef' => $invoice->getPurchaseOrderRef(),
            'paymentTerms' => $invoice->getPaymentTerms(),
            'paymentMethod' => $invoice->getPaymentMethod(),
            'paidAt' => $invoice->getPaidAt()?->format('Y-m-d'),
            'notes' => $invoice->getNotes(),
            'supplierFull' => $invoice->getTiers() instanceof TiersInterface ? $this->tiersSerializer->serialize($invoice->getTiers()) : null,
            'creditNote' => ($cn = $invoice->getCreditNote()) instanceof InvoiceInterface ? ['id' => $cn->getId(), 'number' => $cn->getNumber()] : null,
            'cancelledInvoice' => ($ci = $invoice->getCancelledInvoice()) instanceof InvoiceInterface ? ['id' => $ci->getId(), 'number' => $ci->getNumber()] : null,
            'supplierInvoiceCount' => $invoice->getTiers() instanceof TiersInterface
                ? $this->invoiceRepository->countForTiers($invoice->getTiers()->getId())
                : 0,
            'buyer' => $invoice->getBuyerTiers() instanceof TiersInterface
                ? $this->tiersSerializer->serialize($invoice->getBuyerTiers())
                : null,
            'buyerInvoiceCount' => $invoice->getBuyerTiers() instanceof TiersInterface
                ? $this->invoiceRepository->countAsBuyerForTiers($invoice->getBuyerTiers()->getId())
                : 0,
            'document' => $document instanceof MediaInterface ? [
                'id' => $document->getId(),
                'url' => '/uploads/'.$document->getPath(),
                'originalName' => $document->getOriginalName(),
                'mimeType' => $document->getMimeType(),
            ] : null,
            'ocrJob' => $job instanceof OcrJobInterface ? [
                'id' => $job->getId(),
                'status' => $job->getStatus()->value,
                'statusLabel' => $this->translator->trans($job->getStatus()->getLabelKey()),
                'statusColor' => $job->getStatus()->getBadgeColor(),
                'modelUsed' => $job->getModelUsed(),
                'confidence' => $job->getConfidence(),
                'uncertainFields' => $job->getExtracted()['uncertain_fields'] ?? [],
            ] : null,
            'lines' => array_map($this->serializeLine(...), $invoice->getLines()->toArray()),
        ];
    }

    private function serializeLine(InvoiceLineInterface $line): array
    {
        return [
            'id' => $line->getId(),
            'label' => $line->getLabel(),
            'productCode' => $line->getProductCode(),
            'unit' => $line->getUnit(),
            'quantity' => $line->getQuantity(),
            'unitPriceCents' => $line->getUnitPriceCents(),
            'vatRateBp' => $line->getVatRateBp(),
            'totalNetCents' => $line->getTotalNetCents(),
            'totalGrossCents' => $line->getTotalGrossCents(),
            'reference' => $line->getReference(),
            'description' => $line->getDescription(),
            'discountCents' => $line->getDiscountCents(),
            'origin' => $line->getOrigin(),
            'position' => $line->getPosition(),
        ];
    }
}
