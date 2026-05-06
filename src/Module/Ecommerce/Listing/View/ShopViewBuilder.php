<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\View;

use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Theme\Service\ThemeContext;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Ecommerce\Listing\Serializer\ListingSerializer;

/**
 * Builds the Twig payloads for the public shop views.
 */
final readonly class ShopViewBuilder
{
    public function __construct(
        private ListingRepository $listingRepository,
        private ListingSerializer $listingSerializer,
        private FrontContext $frontContext,
        private ThemeContext $themeContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(int $page, string $locale): array
    {
        $result = $this->listingRepository->findPaginated($page, 12, visibleOnly: true);

        return [
            'listings' => array_map($this->listingSerializer->serialize(...), $result['items']),
            'pagination' => [
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
                'total' => $result['total'],
            ],
            'locale' => $locale,
            'context' => $this->frontContext,
            'showFrontMenus' => true,
            'themeContext' => $this->themeContext,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showView(Listing $listing, string $locale): array
    {
        return [
            'listing' => $this->listingSerializer->serialize($listing),
            'locale' => $locale,
            'context' => $this->frontContext,
            'showFrontMenus' => true,
            'themeContext' => $this->themeContext,
        ];
    }
}
