<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\View;

use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Billing\Invoice\Entity\Invoice;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Invoice\Serializer\InvoiceSerializer;
use Aurora\Module\Billing\Ocr\Entity\OcrJob;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function is_string;

final readonly class InvoicesViewBuilder
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private InvoiceSerializer $invoiceSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function indexView(PaginationRequest $pagination, Request $request): array
    {
        $payload = $this->buildListPayload($pagination, $request);
        $now = new DateTimeImmutable();
        $counts = $this->invoiceRepository->countByStatus();

        return [
            'invoices' => $payload,
            'stats' => [
                'monthGrossCents' => $this->invoiceRepository->sumGrossInPeriod(
                    $now->modify('first day of this month')->setTime(0, 0),
                    $now->modify('last day of this month')->setTime(23, 59, 59),
                ),
                'yearGrossCents' => $this->invoiceRepository->sumGrossInPeriod(
                    $now->modify('first day of January this year')->setTime(0, 0),
                    $now->modify('last day of December this year')->setTime(23, 59, 59),
                ),
                'needsReviewCount' => $counts[InvoiceStatusEnum::NeedsReview->value] ?? 0,
                'totalInvoices' => array_sum($counts),
            ],
            'search' => $pagination->search ?? '',
            'listPath' => $this->urlGenerator->generate('billing_invoices_list'),
            'showPath' => $this->urlGenerator->generate('billing_invoices_show', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('billing_invoices_delete', ['id' => '__id__']),
            'exportXlsxPath' => $this->urlGenerator->generate('billing_invoices_export_xlsx'),
            'importPath' => $this->urlGenerator->generate('billing_ocr_import'),
            'statusOptions' => array_map(static fn (InvoiceStatusEnum $status): array => [
                'value' => $status->value,
                'labelKey' => $status->getLabelKey(),
                'color' => $status->getBadgeColor(),
            ], InvoiceStatusEnum::cases()),
        ];
    }

    /** @return array<string, mixed> */
    public function showView(Invoice $invoice): array
    {
        return [
            'invoice' => $this->invoiceSerializer->serializeDetail($invoice),
            'listPath' => $this->urlGenerator->generate('billing_invoices'),
            'validatePath' => $this->urlGenerator->generate('billing_invoices_validate', ['id' => $invoice->getId()]),
            'deletePath' => $this->urlGenerator->generate('billing_invoices_delete', ['id' => $invoice->getId()]),
            'updatePath' => $this->urlGenerator->generate('billing_invoices_update', ['id' => $invoice->getId()]),
            'tiersUpdatePathTemplate' => $this->urlGenerator->generate('billing_tiers_update', ['id' => '__id__']),
            'lineCreatePath' => $this->urlGenerator->generate('billing_invoices_lines_create', ['id' => $invoice->getId()]),
            'lineUpdatePathTemplate' => $this->urlGenerator->generate('billing_invoices_lines_update', ['id' => $invoice->getId(), 'lineId' => '__lineId__']),
            'lineDeletePathTemplate' => $this->urlGenerator->generate('billing_invoices_lines_delete', ['id' => $invoice->getId(), 'lineId' => '__lineId__']),
            'creditNotePath' => $this->urlGenerator->generate('billing_invoices_credit_note', ['id' => $invoice->getId()]),
            'importPath' => $this->urlGenerator->generate('billing_ocr_import'),
            'ocrRetryPath' => $invoice->getOcrJob() instanceof OcrJob
                ? $this->urlGenerator->generate('billing_ocr_jobs_retry', ['id' => $invoice->getOcrJob()->getId()])
                : null,
            'showPath' => $this->urlGenerator->generate('billing_invoices_show', ['id' => '__id__']),
        ];
    }

    /**
     * Same filters as the list, unbounded — used by the CSV export controller.
     *
     * @return iterable<Invoice>
     */
    public function findForExport(Request $request): iterable
    {
        $search = $request->query->getString('search', '') ?: null;
        $status = $this->resolveStatus($request->query->get('status'));

        return $this->invoiceRepository->findAllMatching($search, $status);
    }

    public function buildListPayload(PaginationRequest $pagination, Request $request): array
    {
        $status = $this->resolveStatus($request->query->get('status'));
        $tiersId = (int) $request->query->get('tiers', '0') ?: null;
        $result = $this->invoiceRepository->findPaginated(
            $pagination->page,
            $pagination->limit,
            $pagination->search,
            $status,
            $tiersId,
        );

        return [
            'success' => true,
            'items' => array_map($this->invoiceSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'status' => $status?->value,
            'counts' => $this->invoiceRepository->countByStatus(),
        ];
    }

    private function resolveStatus(mixed $value): ?InvoiceStatusEnum
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }

        return InvoiceStatusEnum::tryFrom($value);
    }
}
