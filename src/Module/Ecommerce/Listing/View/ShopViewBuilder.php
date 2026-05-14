<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\View;

use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Theme\Service\ThemeContext;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Ecommerce\Listing\Serializer\ListingSerializerInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ecommerce\ListingCategory\Repository\ListingCategoryRepository;

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
        private ListingCategoryRepository $listingCategoryRepository,
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
            'rootCategories' => $this->buildRootCategories($locale),
        ]);
    }

    /**
     * @return list<array{name: string, slug: string}>
     */
    private function buildRootCategories(string $locale): array
    {
        $roots = $this->listingCategoryRepository->findRoots();
        $result = [];
        foreach ($roots as $category) {
            if (!$category->isVisible()) {
                continue;
            }
            $translation = $category->getTranslation($locale);
            if (null === $translation) {
                continue;
            }
            $result[] = [
                'name' => $translation->getName(),
                'slug' => $translation->getSlug(),
            ];
        }

        return $result;
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

    /**
     * Builds the payload for a category landing page. Listings shown include
     * those attached to the current category AND to any descendant category.
     *
     * @return array<string, mixed>
     */
    public function categoryView(ListingCategoryInterface $category, string $locale, int $page): array
    {
        $perPage = 12;

        $descendants = $this->listingCategoryRepository->findDescendantsOf($category);
        $categoryIds = [(int) $category->getId()];
        foreach ($descendants as $descendant) {
            $descendantId = $descendant->getId();
            if (null !== $descendantId) {
                $categoryIds[] = $descendantId;
            }
        }

        $paginated = $this->listingRepository->findVisibleByCategoryIdsPaginated($categoryIds, $page, $perPage);
        $total = $paginated['total'];
        $totalPages = (int) max(1, (int) ceil($total / $perPage));

        $translation = $category->getTranslation($locale);
        $name = null !== $translation ? $translation->getName() : '';
        $description = null !== $translation ? $translation->getDescription() : null;
        $seoTitle = null !== $translation ? $translation->getSeoTitle() : null;
        $seoDescription = null !== $translation ? $translation->getSeoDescription() : null;
        $slug = null !== $translation ? $translation->getSlug() : '';

        $breadcrumb = [];
        $cursor = $category->getParent();
        while (null !== $cursor) {
            $parentTranslation = $cursor->getTranslation($locale);
            $breadcrumb[] = [
                'name' => null !== $parentTranslation ? $parentTranslation->getName() : '',
                'slug' => null !== $parentTranslation ? $parentTranslation->getSlug() : '',
            ];
            $cursor = $cursor->getParent();
        }
        $breadcrumb = array_reverse($breadcrumb);

        return [
            'category' => [
                'id' => $category->getId(),
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'seoTitle' => $seoTitle,
                'seoDescription' => $seoDescription,
            ],
            'breadcrumb' => $breadcrumb,
            'listings' => array_map($this->listingSerializer->serialize(...), $paginated['items']),
            'page' => $paginated['page'],
            'perPage' => $paginated['perPage'],
            'total' => $total,
            'totalPages' => $totalPages,
            'locale' => $locale,
            'context' => $this->context,
            'showFrontMenus' => true,
            'themeContext' => $this->themeContext,
        ];
    }
}
