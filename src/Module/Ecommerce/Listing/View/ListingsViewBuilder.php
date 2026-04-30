<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\View;

use Aurora\Core\Validation\DTO\PaginationRequest;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the Twig payload for the admin listings index view.
 */
final readonly class ListingsViewBuilder
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @param array<string, mixed> $listPayload
     *
     * @return array<string, mixed>
     */
    public function indexView(PaginationRequest $pagination, array $listPayload): array
    {
        return [
            'listings' => $listPayload,
            'search' => $pagination->search ?? '',
            'createPath' => $this->urlGenerator->generate('ecommerce_listings_create'),
            'updatePath' => $this->urlGenerator->generate('ecommerce_listings_update', ['id' => '__id__']),
            'deletePath' => $this->urlGenerator->generate('ecommerce_listings_delete', ['id' => '__id__']),
            'showPath' => $this->urlGenerator->generate('ecommerce_listings_show', ['id' => '__id__']),
            'productsPath' => $this->urlGenerator->generate('ecommerce_listings_products'),
        ];
    }
}
