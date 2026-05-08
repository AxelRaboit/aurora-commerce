<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Controller\Frontend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\FrontLocaleTrait;
use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Theme\Service\ThemeResolver;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Ecommerce\Listing\View\ShopViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShopController extends AbstractController
{
    use FrontLocaleTrait;

    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly FrontContext $frontContext,
        private readonly ThemeResolver $themeResolver,
        private readonly ShopViewBuilder $viewBuilder,
    ) {}

    #[Route('/{locale}/shop', name: 'frontend_shop_index', requirements: ['locale' => '[a-z]{2}'], methods: [HttpMethodEnum::Get->value], priority: 8)]
    public function index(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $page = max(1, (int) $request->query->get('page', '1'));

        return $this->render($this->themeResolver->resolve('shop_index'), $this->viewBuilder->indexView($page, $locale));
    }

    #[Route('/{locale}/shop/{slug}', name: 'frontend_shop_product', requirements: ['locale' => '[a-z]{2}', 'slug' => '[a-z0-9-]+'], methods: [HttpMethodEnum::Get->value], priority: 8)]
    public function show(string $locale, string $slug, Request $request): Response
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $listing = $this->listingRepository->findOneBySlug($slug);
        if (!$listing instanceof Listing || !$listing->isVisibleOnShop()) {
            throw $this->createNotFoundException();
        }

        return $this->render($this->themeResolver->resolve('shop_product'), $this->viewBuilder->showView($listing, $locale));
    }
}
