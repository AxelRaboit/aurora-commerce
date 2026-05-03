<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\View;

use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Billing\Invoice\Entity\Supplier;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Invoice\Repository\SupplierRepository;
use Aurora\Module\Billing\Invoice\Serializer\InvoiceSerializer;
use Aurora\Module\Billing\Invoice\Serializer\SupplierSerializer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class SuppliersViewBuilder
{
    public function __construct(
        private SupplierRepository $supplierRepository,
        private SupplierSerializer $supplierSerializer,
        private InvoiceRepository $invoiceRepository,
        private InvoiceSerializer $invoiceSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function indexView(PaginationRequest $pagination): array
    {
        return [
            'suppliers' => $this->buildListPayload($pagination),
            'search' => $pagination->search ?? '',
            'listPath' => $this->urlGenerator->generate('billing_suppliers_list'),
            'deletePath' => $this->urlGenerator->generate('billing_suppliers_delete', ['id' => '__id__']),
            'showPath' => $this->urlGenerator->generate('billing_suppliers_show', ['id' => '__id__']),
        ];
    }

    /** @return array<string, mixed> */
    public function showView(Supplier $supplier): array
    {
        // Initial first-page payload of invoices linked to this supplier — Vue takes over from there.
        $firstPage = $this->invoiceRepository->findPaginated(1, 20, null, null, $supplier->getId());

        return [
            'supplier' => $this->supplierSerializer->serialize($supplier),
            'invoices' => [
                'success' => true,
                'items' => array_map($this->invoiceSerializer->serialize(...), $firstPage['items']),
                'total' => $firstPage['total'],
                'page' => $firstPage['page'],
                'totalPages' => $firstPage['totalPages'],
            ],
            'stats' => [
                'totalInvoiced' => $this->invoiceRepository->sumGrossForSupplier($supplier->getId()),
                'invoiceCount' => $this->invoiceRepository->countForSupplier($supplier->getId()),
            ],
            'listPath' => $this->urlGenerator->generate('billing_suppliers'),
            'invoicesListPath' => $this->urlGenerator->generate('billing_invoices_list'),
            'invoiceShowPath' => $this->urlGenerator->generate('billing_invoices_show', ['id' => '__id__']),
            'updatePath' => $this->urlGenerator->generate('billing_suppliers_update', ['id' => $supplier->getId()]),
            'deletePath' => $this->urlGenerator->generate('billing_suppliers_delete', ['id' => $supplier->getId()]),
            'statusOptions' => array_map(static fn (InvoiceStatusEnum $status): array => [
                'value' => $status->value,
                'labelKey' => $status->getLabelKey(),
                'color' => $status->getBadgeColor(),
            ], InvoiceStatusEnum::cases()),
        ];
    }

    public function buildListPayload(PaginationRequest $pagination): array
    {
        $result = $this->supplierRepository->findPaginated($pagination->page, $pagination->limit, $pagination->search);

        return [
            'success' => true,
            'items' => array_map($this->supplierSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
