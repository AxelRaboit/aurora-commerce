<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Deal\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Crm\Deal\Entity\Deal;
use Aurora\Module\Crm\Deal\Serializer\DealSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/crm/deals/{id}', name: 'crm_deals_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
#[IsGranted('crm.deals.manage')]
final class DealDetailController extends AbstractController
{
    public function __construct(private readonly DealSerializer $dealSerializer) {}

    public function __invoke(Deal $deal): Response
    {
        return $this->render('@Crm/admin/deals/show.html.twig', [
            'deal' => $this->dealSerializer->serialize($deal),
            'backPath' => $this->generateUrl('crm_deals'),
            'updatePath' => $this->generateUrl('crm_deals_update', ['id' => $deal->getId()]),
            'deletePath' => $this->generateUrl('crm_deals_delete', ['id' => $deal->getId()]),
            'updateStagePath' => $this->generateUrl('crm_deals_stage', ['id' => $deal->getId()]),
        ]);
    }
}
