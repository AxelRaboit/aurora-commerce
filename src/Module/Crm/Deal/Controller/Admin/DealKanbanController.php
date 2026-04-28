<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/crm/deals/kanban', name: 'crm_deals_kanban', methods: [HttpMethodEnum::Get->value])]
#[IsGranted('crm.deals.manage')]
final class DealKanbanController extends AbstractController
{
    public function __construct(
        private readonly DealsController $dealsController,
    ) {}

    public function __invoke(PaginationRequest $pagination): Response
    {
        return $this->dealsController->renderApp(
            $pagination,
            initialView: 'kanban',
            kanbanColumns: $this->dealsController->buildKanbanColumns(),
        );
    }
}
