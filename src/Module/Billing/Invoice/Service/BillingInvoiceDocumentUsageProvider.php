<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Service;

use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Ged\Document\Contract\DocumentUsageProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * Reports invoices whose source/attached PDF is the given GED document
 * (`BillingInvoice.document`).
 */
final readonly class BillingInvoiceDocumentUsageProvider implements DocumentUsageProviderInterface
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {}

    public function findUsages(int $documentId): array
    {
        $invoices = $this->invoiceRepository->createQueryBuilder('i')
            ->andWhere('i.document = :id')
            ->setParameter('id', $documentId)
            ->getQuery()
            ->getResult();

        $usages = [];
        foreach ($invoices as $invoice) {
            $usages[] = [
                'type' => 'billing.invoice',
                'label' => $invoice->getNumber() ?? $invoice->getReference() ?? '#'.$invoice->getId(),
                'detail' => $this->translator->trans('backend.ged.documents.usage.billing_invoice'),
                'href' => $this->safeUrl('backend_billing_invoices_show', ['id' => (int) $invoice->getId()]),
            ];
        }

        return $usages;
    }

    /** @param array<string, mixed> $params */
    private function safeUrl(string $route, array $params): ?string
    {
        try {
            return $this->urlGenerator->generate($route, $params);
        } catch (Throwable) {
            return null;
        }
    }
}
