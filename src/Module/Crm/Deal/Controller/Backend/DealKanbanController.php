<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Crm\Deal\View\DealsViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/crm/deals/kanban', name: 'backend_crm_deals_kanban', methods: [HttpMethodEnum::Get->value])]
#[IsGranted('crm.deals.manage')]
final class DealKanbanController extends AbstractController
{
    public function __construct(
        private readonly DealsViewBuilder $viewBuilder,
    ) {}

    public function __invoke(PaginationRequest $pagination): Response
    {
        return $this->render(
            '@Crm/backend/deals/index.html.twig',
            $this->viewBuilder->indexView(
                $pagination,
                initialView: 'kanban',
                kanbanColumns: $this->viewBuilder->buildKanbanColumns(),
            ),
        );
    }
}
