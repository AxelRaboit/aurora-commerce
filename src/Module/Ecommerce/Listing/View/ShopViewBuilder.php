<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\View;

use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Theme\Service\ThemeContext;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Ecommerce\Listing\Serializer\ListingSerializerInterface;

/**
 * Builds the Twig payloads for the public shop views.
 */
final readonly class ShopViewBuilder
{
    public function __construct(
        private ListingRepository $listingRepository,
        private ListingSerializerInterface $listingSerializer,
        private Context $context,
        private ThemeContext $themeContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(int $page, string $locale, string $searchPath): array
    {
        return array_merge($this->pageData($page, null), [
            'locale' => $locale,
            'context' => $this->context,
            'showFrontMenus' => true,
            'themeContext' => $this->themeContext,
            'searchPath' => $searchPath,
        ]);
    }

    /** @return array{listings: array<mixed>, page: int, totalPages: int, total: int} */
    public function pageData(int $page, ?string $search): array
    {
        $result = $this->listingRepository->findPaginated($page, 12, search: $search, visibleOnly: true);

        return [
            'listings' => array_map($this->listingSerializer->serialize(...), $result['items']),
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'total' => $result['total'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showView(ListingInterface $listing, string $locale): array
    {
        return [
            'listing' => $this->listingSerializer->serialize($listing),
            'locale' => $locale,
            'context' => $this->context,
            'showFrontMenus' => true,
            'themeContext' => $this->themeContext,
        ];
    }
}
