<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Controller\Frontend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Frontend\Controller\LocaleTrait;
use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Configuration\Theme\Service\ThemeResolver;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Ecommerce\Listing\View\ShopViewBuilder;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ecommerce\ListingCategory\Repository\ListingCategoryRepository;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Aurora\Module\Ecommerce\ListingTag\Repository\ListingTagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShopController extends AbstractController
{
    use LocaleTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly Context $context,
        private readonly ThemeResolver $themeResolver,
        private readonly ShopViewBuilder $viewBuilder,
        private readonly ListingCategoryRepository $listingCategoryRepository,
        private readonly ListingTagRepository $listingTagRepository,
    ) {}

    #[Route('/{locale}/shop', name: 'frontend_shop_index', requirements: ['locale' => '[a-z]{2}'], methods: [HttpMethodEnum::Get->value], priority: 8)]
    public function index(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($this->context, $locale);
        $request->setLocale($locale);

        $page = max(1, (int) $request->query->get('page', '1'));
        $searchPath = $this->generateUrl('frontend_shop_search', ['locale' => $locale]);

        return $this->render($this->themeResolver->resolve('ecommerce/shop/index'), $this->viewBuilder->indexView($page, $locale, $searchPath));
    }

    #[Route('/{locale}/shop/search', name: 'frontend_shop_search', requirements: ['locale' => '[a-z]{2}'], methods: [HttpMethodEnum::Get->value], priority: 8)]
    public function search(string $locale, Request $request): JsonResponse
    {
        $this->assertActiveLocale($this->context, $locale);

        $query = mb_trim($request->query->getString('q', ''));
        $page = max(1, $request->query->getInt('page', 1));

        return $this->jsonSuccess(
            $this->viewBuilder->pageData($page, '' !== $query ? $query : null),
        );
    }

    #[Route('/{locale}/shop/category/{slug}', name: 'frontend_shop_category', requirements: ['locale' => '[a-z]{2}', 'slug' => '[a-z0-9-]+'], methods: [HttpMethodEnum::Get->value], priority: 9)]
    public function showCategory(string $locale, string $slug, Request $request): Response
    {
        $this->assertActiveLocale($this->context, $locale);
        $request->setLocale($locale);

        $category = $this->listingCategoryRepository->findOneBySlug($slug, $locale);
        if (!$category instanceof ListingCategoryInterface || !$category->isVisible()) {
            throw $this->createNotFoundException();
        }

        $page = max(1, (int) $request->query->get('page', '1'));

        return $this->render(
            $this->themeResolver->resolve('ecommerce/shop/category'),
            $this->viewBuilder->categoryView($category, $locale, $page),
        );
    }

    #[Route('/{locale}/shop/tag/{slug}', name: 'frontend_shop_tag', requirements: ['locale' => '[a-z]{2}', 'slug' => '[a-z0-9-]+'], methods: [HttpMethodEnum::Get->value], priority: 9)]
    public function showTag(string $locale, string $slug, Request $request): Response
    {
        $this->assertActiveLocale($this->context, $locale);
        $request->setLocale($locale);

        $tag = $this->listingTagRepository->findOneBySlug($slug, $locale);
        if (!$tag instanceof ListingTagInterface || !$tag->isVisible()) {
            throw $this->createNotFoundException();
        }

        $page = max(1, (int) $request->query->get('page', '1'));

        return $this->render(
            $this->themeResolver->resolve('ecommerce/shop/tag'),
            $this->viewBuilder->tagView($tag, $locale, $page),
        );
    }

    #[Route('/{locale}/shop/{slug}', name: 'frontend_shop_product', requirements: ['locale' => '[a-z]{2}', 'slug' => '[a-z0-9-]+'], methods: [HttpMethodEnum::Get->value], priority: 8)]
    public function show(string $locale, string $slug, Request $request): Response
    {
        $this->assertActiveLocale($this->context, $locale);
        $request->setLocale($locale);

        $listing = $this->listingRepository->findOneBySlug($slug);
        if (!$listing instanceof Listing || !$listing->isVisibleOnShop()) {
            throw $this->createNotFoundException();
        }

        return $this->render($this->themeResolver->resolve('ecommerce/shop/product'), $this->viewBuilder->showView($listing, $locale));
    }
}
