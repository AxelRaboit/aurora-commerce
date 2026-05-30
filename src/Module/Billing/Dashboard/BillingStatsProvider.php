<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Dashboard;

use Aurora\Core\Dashboard\DashboardStatsProviderInterface;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Invoice\Repository\TiersRepository;
use Aurora\Module\Billing\Ocr\Repository\OcrJobRepository;

/**
 * Billing slice of the backend dashboard. Lives in the Billing module so the
 * General dashboard never imports Billing repositories.
 */
final readonly class BillingStatsProvider implements DashboardStatsProviderInterface
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private TiersRepository $tiersRepository,
        private OcrJobRepository $ocrJobRepository,
    ) {}

    public function getModuleKey(): string
    {
        return 'billing';
    }

    public function getStats(): array
    {
        $byStatus = $this->invoiceRepository->countByStatus();

        return [
            'billing' => [
                'invoices' => array_sum($byStatus),
                'byStatus' => $byStatus,
                'suppliers' => $this->tiersRepository->count(['type' => TiersTypeEnum::Supplier]),
                'ocrJobs' => $this->ocrJobRepository->count([]),
                'needingReview' => $byStatus[InvoiceStatusEnum::NeedsReview->value] ?? 0,
                'totalGrossCents' => $this->invoiceRepository->getTotalGrossCents(),
            ],
        ];
    }
}
