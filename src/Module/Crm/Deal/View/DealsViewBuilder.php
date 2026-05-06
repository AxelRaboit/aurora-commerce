<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\View;

use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use Aurora\Module\Crm\Deal\Repository\DealRepository;
use Aurora\Module\Crm\Deal\Serializer\DealSerializer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payloads for the admin deals list/kanban views. Centralises
 * URL generation, serialisation and stage column assembly so the controller
 * stays focused on HTTP flow.
 */
final readonly class DealsViewBuilder
{
    public function __construct(
        private DealRepository $dealRepository,
        private DealSerializer $dealSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @param array<string, list<array<string, mixed>>>|null $kanbanColumns
     *
     * @return array<string, mixed>
     */
    public function indexView(PaginationRequest $pagination, string $initialView, ?array $kanbanColumns): array
    {
        return [
            'initialView' => $initialView,
            'kanbanColumns' => $kanbanColumns,
            'deals' => $this->buildListPayload($pagination),
            'search' => $pagination->search ?? '',
            'stages' => array_map(static fn (DealStageEnum $stage): string => $stage->value, DealStageEnum::cases()),
            'createPath' => $this->urlGenerator->generate('backend_crm_deals_create'),
            'updatePath' => $this->urlGenerator->generate('backend_crm_deals_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('backend_crm_deals_delete', ['id' => '__id__']),
            'listPath' => $this->urlGenerator->generate('backend_crm_deals_list'),
            'kanbanColumnsPath' => $this->urlGenerator->generate('backend_crm_deals_kanban_columns'),
            'contactsListPath' => $this->urlGenerator->generate('backend_crm_contacts_list'),
            'companiesListPath' => $this->urlGenerator->generate('backend_crm_companies_list'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildListPayload(PaginationRequest $pagination): array
    {
        $result = $this->dealRepository->findPaginated($pagination->page, search: $pagination->search);

        return [
            'success' => true,
            'items' => array_map($this->dealSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function buildKanbanColumns(): array
    {
        $stages = DealStageEnum::cases();
        $columns = [];
        foreach ($stages as $stage) {
            $result = $this->dealRepository->findPaginated(1, 100, stage: $stage);
            $columns[$stage->value] = array_map($this->dealSerializer->serialize(...), $result['items']);
        }

        return $columns;
    }
}
