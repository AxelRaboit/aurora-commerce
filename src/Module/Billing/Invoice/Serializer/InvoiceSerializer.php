<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Serializer;

use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Entity\InvoiceLine;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class InvoiceSerializer
{
    public function __construct(
        private TranslatorInterface $translator,
        private SupplierSerializer $supplierSerializer,
    ) {}

    public function serialize(Invoice $invoice): array
    {
        $supplier = $invoice->getSupplier();
        $status = $invoice->getStatus();

        return [
            'id' => $invoice->getId(),
            'number' => $invoice->getNumber(),
            'status' => $status->value,
            'statusLabel' => $this->translator->trans($status->getLabelKey()),
            'statusColor' => $status->getBadgeColor(),
            'supplier' => null === $supplier ? null : [
                'id' => $supplier->getId(),
                'name' => $supplier->getName(),
            ],
            'issuedAt' => $invoice->getIssuedAt()?->format('Y-m-d'),
            'dueAt' => $invoice->getDueAt()?->format('Y-m-d'),
            'currency' => $invoice->getCurrency()->value,
            'totalNetCents' => $invoice->getTotalNetCents(),
            'totalVatCents' => $invoice->getTotalVatCents(),
            'totalGrossCents' => $invoice->getTotalGrossCents(),
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
        $supplier = $invoice->getSupplier();

        return [
            ...$base,
            'purchaseOrderRef' => $invoice->getPurchaseOrderRef(),
            'paymentTerms' => $invoice->getPaymentTerms(),
            'paymentMethod' => $invoice->getPaymentMethod(),
            'paidAt' => $invoice->getPaidAt()?->format('Y-m-d'),
            'notes' => $invoice->getNotes(),
            'supplierFull' => null === $supplier ? null : $this->supplierSerializer->serialize($supplier),
            'document' => null === $document ? null : [
                'id' => $document->getId(),
                'url' => '/uploads/'.$document->getPath(),
                'originalName' => $document->getOriginalName(),
                'mimeType' => $document->getMimeType(),
            ],
            'ocrJob' => null === $job ? null : [
                'id' => $job->getId(),
                'status' => $job->getStatus()->value,
                'statusLabel' => $this->translator->trans($job->getStatus()->getLabelKey()),
                'statusColor' => $job->getStatus()->getBadgeColor(),
                'modelUsed' => $job->getModelUsed(),
                'confidence' => $job->getConfidence(),
            ],
            'lines' => array_map($this->serializeLine(...), $invoice->getLines()->toArray()),
        ];
    }

    private function serializeLine(InvoiceLine $line): array
    {
        return [
            'id' => $line->getId(),
            'label' => $line->getLabel(),
            'sku' => $line->getSku(),
            'unit' => $line->getUnit(),
            'quantity' => $line->getQuantity(),
            'unitPriceCents' => $line->getUnitPriceCents(),
            'vatRateBp' => $line->getVatRateBp(),
            'totalNetCents' => $line->getTotalNetCents(),
            'totalGrossCents' => $line->getTotalGrossCents(),
            'position' => $line->getPosition(),
        ];
    }
}
