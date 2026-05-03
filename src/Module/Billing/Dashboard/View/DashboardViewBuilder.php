<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Dashboard\View;

use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Invoice\Serializer\InvoiceSerializer;
use DateTimeImmutable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Billing dashboard payload — KPIs + recent items needing attention.
 */
final readonly class DashboardViewBuilder
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private InvoiceSerializer $invoiceSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(): array
    {
        $now = new DateTimeImmutable('now');
        $monthStart = $now->modify('first day of this month')->setTime(0, 0);
        $monthEnd = $now->modify('last day of this month')->setTime(23, 59, 59);
        $yearStart = $now->modify('first day of January this year')->setTime(0, 0);
        $yearEnd = $now->modify('last day of December this year')->setTime(23, 59, 59);

        $counts = $this->invoiceRepository->countByStatus();
        $needsReviewItems = $this->invoiceRepository->findRecentNeedingReview(5);

        return [
            'stats' => [
                'monthGrossCents' => $this->invoiceRepository->sumGrossInPeriod($monthStart, $monthEnd),
                'yearGrossCents' => $this->invoiceRepository->sumGrossInPeriod($yearStart, $yearEnd),
                'needsReviewCount' => $counts[InvoiceStatusEnum::NeedsReview->value] ?? 0,
                'totalInvoices' => array_sum($counts),
            ],
            'countsByStatus' => $counts,
            'topSuppliers' => $this->invoiceRepository->topSuppliers(5),
            'needsReview' => array_map($this->invoiceSerializer->serialize(...), $needsReviewItems),
            'invoicesPath' => $this->urlGenerator->generate('billing_invoices'),
            'showPath' => $this->urlGenerator->generate('billing_invoices_show', ['id' => '__id__']),
            'importPath' => $this->urlGenerator->generate('billing_ocr_import'),
            'statusOptions' => array_map(static fn (InvoiceStatusEnum $status): array => [
                'value' => $status->value,
                'labelKey' => $status->getLabelKey(),
                'color' => $status->getBadgeColor(),
            ], InvoiceStatusEnum::cases()),
        ];
    }
}
