<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\View\DealDetailViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/crm/deals/{id}', name: 'backend_crm_deals_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
#[IsGranted('crm.deals.manage')]
final class DealDetailController extends AbstractController
{
    public function __construct(private readonly DealDetailViewBuilder $viewBuilder) {}

    public function __invoke(Deal $deal): Response
    {
        return $this->render('@Crm/backend/deals/show.html.twig', $this->viewBuilder->showView($deal));
    }
}
