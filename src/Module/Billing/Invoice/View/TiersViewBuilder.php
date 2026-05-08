<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\View;

use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Enum\InvoiceStatusEnum;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Invoice\Repository\TiersRepository;
use Aurora\Module\Billing\Invoice\Serializer\InvoiceSerializer;
use Aurora\Module\Billing\Invoice\Serializer\TiersSerializer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class TiersViewBuilder
{
    public function __construct(
        private TiersRepository $tiersRepository,
        private TiersSerializer $tiersSerializer,
        private InvoiceRepository $invoiceRepository,
        private InvoiceSerializer $invoiceSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function indexView(PaginationRequest $pagination, ?TiersTypeEnum $type): array
    {
        return [
            'tiers' => $this->buildListPayload($pagination, $type),
            'search' => $pagination->search ?? '',
            'typeFilter' => $type instanceof TiersTypeEnum ? $type->value : '',
            'typeOptions' => array_map(static fn (TiersTypeEnum $t): array => [
                'value' => $t->value,
                'labelKey' => $t->getLabelKey(),
            ], TiersTypeEnum::cases()),
            'listPath' => $this->urlGenerator->generate('backend_billing_tiers_list'),
            'deletePath' => $this->urlGenerator->generate('backend_billing_tiers_delete', ['id' => '__id__']),
            'showPath' => $this->urlGenerator->generate('backend_billing_tiers_show', ['id' => '__id__']),
        ];
    }

    public function showView(Tiers $tiers): array
    {
        $firstPage = $this->invoiceRepository->findPaginated(1, 20, null, null, $tiers->getId());

        return [
            'tiers' => $this->tiersSerializer->serialize($tiers),
            'invoices' => [
                'success' => true,
                'items' => array_map($this->invoiceSerializer->serialize(...), $firstPage['items']),
                'total' => $firstPage['total'],
                'page' => $firstPage['page'],
                'totalPages' => $firstPage['totalPages'],
            ],
            'stats' => [
                'totalInvoiced' => $this->invoiceRepository->sumGrossForTiers($tiers->getId()),
                'invoiceCount' => $this->invoiceRepository->countForTiers($tiers->getId()),
            ],
            'listPath' => $this->urlGenerator->generate('backend_billing_tiers'),
            'invoicesListPath' => $this->urlGenerator->generate('backend_billing_invoices_list'),
            'invoiceShowPath' => $this->urlGenerator->generate('backend_billing_invoices_show', ['id' => '__id__']),
            'updatePath' => $this->urlGenerator->generate('backend_billing_tiers_update', ['id' => $tiers->getId()]),
            'deletePath' => $this->urlGenerator->generate('backend_billing_tiers_delete', ['id' => $tiers->getId()]),
            'statusOptions' => array_map(static fn (InvoiceStatusEnum $s): array => [
                'value' => $s->value,
                'labelKey' => $s->getLabelKey(),
                'color' => $s->getBadgeColor(),
            ], InvoiceStatusEnum::cases()),
        ];
    }

    public function buildListPayload(PaginationRequest $pagination, ?TiersTypeEnum $type): array
    {
        $result = $this->tiersRepository->findPaginated($pagination->page, $pagination->limit, $pagination->search, $type);

        return [
            'success' => true,
            'items' => array_map($this->tiersSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }
}
