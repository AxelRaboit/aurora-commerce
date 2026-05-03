<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Compliance\View;

use Aurora\Core\Audit\Repository\AuditLogRepository;
use Aurora\Module\Billing\Compliance\Service\SequenceChecker;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;

final readonly class ComplianceViewBuilder
{
    public function __construct(
        private SequenceChecker $sequenceChecker,
        private InvoiceRepository $invoiceRepository,
        private AuditLogRepository $auditLogRepository,
    ) {}

    public function buildReport(): array
    {
        $sequenceChecks = $this->sequenceChecker->check();
        $sequenceStatus = $this->rollupStatus(array_column($sequenceChecks, 'status'));
        $gapCount = array_sum(array_map(static fn ($c) => count($c['gaps']), $sequenceChecks));

        $overdueForArchiving = $this->invoiceRepository->findOverdueForArchiving(6);
        $archiveStatus = count($overdueForArchiving) === 0 ? 'ok' : 'warning';

        $auditAnomalies = $this->auditLogRepository->findBillingAnomalies();
        $auditStatus = count($auditAnomalies) === 0 ? 'ok' : 'warning';

        $counts = $this->invoiceRepository->countByStatus();
        $totalValidated = ($counts['validated'] ?? 0) + ($counts['paid'] ?? 0) + ($counts['archived'] ?? 0);

        $overall = $this->rollupStatus([$sequenceStatus, $archiveStatus, $auditStatus]);

        return [
            'overall' => $overall,
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'checks' => [
                'sequence' => [
                    'status' => $sequenceStatus,
                    'gapCount' => $gapCount,
                    'years' => $sequenceChecks,
                ],
                'archive' => [
                    'status' => $archiveStatus,
                    'count' => count($overdueForArchiving),
                    'invoices' => $overdueForArchiving,
                    'thresholdYears' => 6,
                ],
                'audit' => [
                    'status' => $auditStatus,
                    'anomalyCount' => count($auditAnomalies),
                    'anomalies' => $auditAnomalies,
                ],
            ],
            'stats' => [
                'totalIssued' => $totalValidated,
                'byStatus' => $counts,
            ],
        ];
    }

    private function rollupStatus(array $statuses): string
    {
        if (in_array('error', $statuses, true)) return 'error';
        if (in_array('warning', $statuses, true)) return 'warning';
        return 'ok';
    }
}
