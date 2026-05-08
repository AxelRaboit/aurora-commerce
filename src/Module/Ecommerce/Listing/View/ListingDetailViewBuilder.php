<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\View;

use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Listing\Serializer\ListingSerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin listing detail view.
 */
final readonly class ListingDetailViewBuilder
{
    public function __construct(
        private ListingSerializerInterface $listingSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function showView(Listing $listing): array
    {
        return [
            'listing' => $this->listingSerializer->serialize($listing),
            'backPath' => $this->urlGenerator->generate('backend_ecommerce_listings'),
            'updatePath' => $this->urlGenerator->generate('backend_ecommerce_listings_update', ['id' => $listing->getId()]),
            'deletePath' => $this->urlGenerator->generate('backend_ecommerce_listings_delete', ['id' => $listing->getId()]),
        ];
    }
}
