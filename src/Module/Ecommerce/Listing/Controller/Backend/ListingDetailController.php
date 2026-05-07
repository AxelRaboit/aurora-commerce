<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Listing\View\ListingDetailViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/ecommerce/listings/{id}', name: 'backend_ecommerce_listings_show', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
#[IsGranted('ecommerce.listings.view')]
final class ListingDetailController extends AbstractController
{
    public function __construct(private readonly ListingDetailViewBuilder $viewBuilder) {}

    public function __invoke(Listing $listing): Response
    {
        return $this->render('@Ecommerce/backend/listings/show.html.twig', $this->viewBuilder->showView($listing));
    }
}
