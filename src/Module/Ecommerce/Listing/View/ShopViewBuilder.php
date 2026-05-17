<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\View;

use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Configuration\Theme\Service\ThemeContext;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Ecommerce\Listing\Serializer\ListingSerializerInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ecommerce\ListingCategory\Repository\ListingCategoryRepository;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Aurora\Module\Ecommerce\ListingTag\Repository\ListingTagRepository;

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
        private ListingTagRepository $listingTagRepository,
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
            'availableTags' => $this->buildAvailableTags($locale),
        ]);
    }

    /**
     * Lists all visible tags translated for the active locale. Tags are flat
     * (no parent/child), so we expose the full set sorted alphabetically for
     * discoverability on the shop index.
     *
     * @return list<array{name: string, slug: string, color: string}>
     */
    private function buildAvailableTags(string $locale): array
    {
        $tags = $this->listingTagRepository->findAllOrdered($locale);
        $result = [];
        foreach ($tags as $tag) {
            if (!$tag->isVisible()) {
                continue;
            }

            $translation = $tag->getTranslation($locale);
            if (null === $translation) {
                continue;
            }

            $result[] = [
                'name' => $translation->getName(),
                'slug' => $translation->getSlug(),
                'color' => $tag->getColor(),
            ];
        }

        return $result;
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
        $totalPages = max(1, (int) ceil($total / $perPage));

        $translation = $category->getTranslation($locale);
        $name = $translation?->getName() ?? '';
        $description = $translation?->getDescription();
        $seoTitle = $translation?->getSeoTitle();
        $seoDescription = $translation?->getSeoDescription();
        $slug = $translation?->getSlug() ?? '';

        $breadcrumb = [];
        $cursor = $category->getParent();
        while ($cursor instanceof ListingCategoryInterface) {
            $parentTranslation = $cursor->getTranslation($locale);
            $breadcrumb[] = [
                'name' => $parentTranslation?->getName() ?? '',
                'slug' => $parentTranslation?->getSlug() ?? '',
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

    /**
     * Builds the payload for a tag landing page. Tags are flat, so unlike
     * categoryView there is no descendant traversal and no breadcrumb.
     *
     * @return array<string, mixed>
     */
    public function tagView(ListingTagInterface $tag, string $locale, int $page): array
    {
        $perPage = 12;

        $tagId = $tag->getId();
        $tagIds = null !== $tagId ? [$tagId] : [];

        $paginated = $this->listingRepository->findVisibleByTagIdsPaginated($tagIds, $page, $perPage);
        $total = $paginated['total'];
        $totalPages = max(1, (int) ceil($total / $perPage));

        $translation = $tag->getTranslation($locale);
        $name = $translation?->getName() ?? '';
        $description = $translation?->getDescription();
        $slug = $translation?->getSlug() ?? '';

        return [
            'tag' => [
                'id' => $tagId,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'color' => $tag->getColor(),
            ],
            'otherTags' => $this->buildOtherTags($tag, $locale),
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

    /**
     * Up to 8 visible tags (excluding the current one) for cross-navigation on
     * a tag page, sorted alphabetically via findAllOrdered().
     *
     * @return list<array{name: string, slug: string, color: string}>
     */
    private function buildOtherTags(ListingTagInterface $current, string $locale): array
    {
        $currentId = $current->getId();
        $tags = $this->listingTagRepository->findAllOrdered($locale);
        $result = [];
        foreach ($tags as $tag) {
            if (!$tag->isVisible()) {
                continue;
            }

            if (null !== $currentId && $tag->getId() === $currentId) {
                continue;
            }

            $translation = $tag->getTranslation($locale);
            if (null === $translation) {
                continue;
            }

            $result[] = [
                'name' => $translation->getName(),
                'slug' => $translation->getSlug(),
                'color' => $tag->getColor(),
            ];
            if (count($result) >= 8) {
                break;
            }
        }

        return $result;
    }
}
