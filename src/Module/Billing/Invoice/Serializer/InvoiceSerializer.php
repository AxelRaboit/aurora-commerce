<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Serializer;

use Aurora\Core\Media\Entity\Media;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class InvoiceSerializer
{
    public function __construct(
        private TranslatorInterface $translator,
        private TiersSerializer $tiersSerializer,
        private InvoiceRepository $invoiceRepository,
    ) {}

    public function serialize(Invoice $invoice): array
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
            'supplier' => $invoice->getTiers() instanceof Tiers ? [
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
    public function serializeDetail(Invoice $invoice): array
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
            'supplierFull' => $invoice->getTiers() instanceof Tiers ? $this->tiersSerializer->serialize($invoice->getTiers()) : null,
            'creditNote' => ($cn = $invoice->getCreditNote()) instanceof Invoice ? ['id' => $cn->getId(), 'number' => $cn->getNumber()] : null,
            'cancelledInvoice' => ($ci = $invoice->getCancelledInvoice()) instanceof Invoice ? ['id' => $ci->getId(), 'number' => $ci->getNumber()] : null,
            'supplierInvoiceCount' => $invoice->getTiers() instanceof Tiers
                ? $this->invoiceRepository->countForTiers($invoice->getTiers()->getId())
                : 0,
            'buyer' => $invoice->getBuyerTiers() instanceof Tiers
                ? $this->tiersSerializer->serialize($invoice->getBuyerTiers())
                : null,
            'document' => $document instanceof Media ? [
                'id' => $document->getId(),
                'url' => '/uploads/'.$document->getPath(),
                'originalName' => $document->getOriginalName(),
                'mimeType' => $document->getMimeType(),
            ] : null,
            'ocrJob' => $job instanceof OcrJob ? [
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

    private function serializeLine(InvoiceLine $line): array
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
