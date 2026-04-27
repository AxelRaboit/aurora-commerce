<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Controller\Front;

use Aurora\Core\Frontend\Controller\FrontLocaleTrait;
use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Theme\Service\ThemeContext;
use Aurora\Core\Theme\Service\ThemeResolver;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Ecommerce\Listing\Serializer\ListingSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShopController extends AbstractController
{
    use FrontLocaleTrait;

    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly ListingSerializer $listingSerializer,
        private readonly FrontContext $frontContext,
        private readonly ThemeResolver $themeResolver,
        private readonly ThemeContext $themeContext,
    ) {}

    #[Route('/{locale}/shop', name: 'front_shop_index', requirements: ['locale' => '[a-z]{2}'], methods: ['GET'], priority: 8)]
    public function index(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $page = max(1, (int) $request->query->get('page', '1'));
        $result = $this->listingRepository->findPaginated($page, 12, visibleOnly: true);

        return $this->render($this->themeResolver->resolve('shop_index'), [
            'listings' => array_map($this->listingSerializer->serialize(...), $result['items']),
            'pagination' => [
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
                'total' => $result['total'],
            ],
            'locale' => $locale,
            'context' => $this->frontContext,
            'themeContext' => $this->themeContext,
        ]);
    }

    #[Route('/{locale}/shop/{slug}', name: 'front_shop_product', requirements: ['locale' => '[a-z]{2}', 'slug' => '[a-z0-9-]+'], methods: ['GET'], priority: 8)]
    public function show(string $locale, string $slug, Request $request): Response
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $listing = $this->listingRepository->findOneBySlug($slug);
        if (!$listing instanceof Listing || !$listing->isVisibleOnShop()) {
            throw $this->createNotFoundException();
        }

        return $this->render($this->themeResolver->resolve('shop_product'), [
            'listing' => $this->listingSerializer->serialize($listing),
            'locale' => $locale,
            'context' => $this->frontContext,
            'themeContext' => $this->themeContext,
        ]);
    }
}
