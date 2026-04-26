<?php

declare(strict_types=1);

namespace App\Module\Crm\Deal\Controller\Admin;

use App\Core\Enum\HttpMethodEnum;
use App\Module\Crm\Deal\Enum\DealStageEnum;
use App\Module\Crm\Deal\Repository\DealRepository;
use App\Module\Crm\Deal\Serializer\DealSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/crm/deals/kanban', name: 'crm_deals_kanban', methods: [HttpMethodEnum::Get->value])]
#[IsGranted('crm.deals.manage')]
final class DealKanbanController extends AbstractController
{
    public function __construct(
        private readonly DealRepository $dealRepository,
        private readonly DealSerializer $dealSerializer,
    ) {}

    public function __invoke(): Response
    {
        $stages = DealStageEnum::cases();
        $columns = [];
        foreach ($stages as $stage) {
            $result = $this->dealRepository->findPaginated(1, 100, stage: $stage);
            $columns[$stage->value] = array_map($this->dealSerializer->serialize(...), $result['items']);
        }

        return $this->render('@Crm/admin/deals/kanban.html.twig', [
            'columns' => $columns,
            'stages' => array_map(fn (DealStageEnum $s) => $s->value, $stages),
            'updateStagePath' => $this->generateUrl('crm_deals_stage', ['id' => '__id__']),
            'listPath' => $this->generateUrl('crm_deals'),
        ]);
    }
}
