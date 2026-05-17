<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Controller\Frontend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Frontend\Controller\LocaleTrait;
use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Configuration\Theme\Service\ThemeResolver;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Manager\CartManagerInterface;
use Aurora\Module\Ecommerce\Cart\Serializer\CartSerializer;
use Aurora\Module\Ecommerce\Cart\View\CartViewBuilder;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    use LocaleTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly CartManagerInterface $cartManager,
        private readonly CartSerializer $cartSerializer,
        private readonly ListingRepository $listingRepository,
        private readonly Context $context,
        private readonly ThemeResolver $themeResolver,
        private readonly CartViewBuilder $viewBuilder,
    ) {}

    #[Route('/{locale}/cart', name: 'frontend_cart', requirements: ['locale' => '[a-z]{2}'], methods: [HttpMethodEnum::Get->value], priority: 8)]
    public function index(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($this->context, $locale);
        $request->setLocale($locale);

        $cart = $this->cartManager->getCurrentCart();

        return $this->render($this->themeResolver->resolve('ecommerce/cart'), $this->viewBuilder->indexView($cart, $locale));
    }

    #[Route('/{locale}/cart/add', name: 'frontend_cart_add', requirements: ['locale' => '[a-z]{2}'], methods: [HttpMethodEnum::Post->value], priority: 8)]
    public function add(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($this->context, $locale);
        $payload = $this->payload($request);
        $listingId = (int) ($payload['listingId'] ?? 0);
        $quantity = max(1, (int) ($payload['quantity'] ?? 1));
        $listing = $this->listingRepository->find($listingId);

        if (null === $listing || !$listing->isVisibleOnShop()) {
            throw $this->createNotFoundException();
        }

        $this->cartManager->addItem($listing, $quantity);

        return $this->respond($request, $locale);
    }

    #[Route('/{locale}/cart/update', name: 'frontend_cart_update', requirements: ['locale' => '[a-z]{2}'], methods: [HttpMethodEnum::Post->value], priority: 8)]
    public function update(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($this->context, $locale);
        $payload = $this->payload($request);
        $listingId = (int) ($payload['listingId'] ?? 0);
        $quantity = (int) ($payload['quantity'] ?? 0);
        $listing = $this->listingRepository->find($listingId);

        if (null !== $listing) {
            $this->cartManager->updateItemQuantity($listing, $quantity);
        }

        return $this->respond($request, $locale);
    }

    #[Route('/{locale}/cart/remove', name: 'frontend_cart_remove', requirements: ['locale' => '[a-z]{2}'], methods: [HttpMethodEnum::Post->value], priority: 8)]
    public function remove(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($this->context, $locale);
        $payload = $this->payload($request);
        $listingId = (int) ($payload['listingId'] ?? 0);
        $listing = $this->listingRepository->find($listingId);

        if (null !== $listing) {
            $this->cartManager->removeItem($listing);
        }

        return $this->respond($request, $locale);
    }

    #[Route('/cart/count', name: 'frontend_cart_count', methods: [HttpMethodEnum::Get->value])]
    public function count(): JsonResponse
    {
        $cart = $this->cartManager->getCurrentCart(false);

        return $this->json(['count' => $cart instanceof Cart ? $cart->getTotalQuantity() : 0]);
    }

    private function isXhr(Request $request): bool
    {
        if ('XMLHttpRequest' === $request->headers->get('X-Requested-With')) {
            return true;
        }

        return str_contains((string) $request->headers->get('Accept', ''), 'application/json');
    }

    private function payload(Request $request): array
    {
        if (str_contains((string) $request->headers->get('Content-Type', ''), 'application/json')) {
            return json_decode((string) $request->getContent(), true) ?? [];
        }

        return $request->request->all();
    }

    private function respond(Request $request, string $locale): Response
    {
        if ($this->isXhr($request)) {
            $cart = $this->cartManager->getCurrentCart();

            return $this->jsonSuccess(['cart' => $this->cartSerializer->serialize($cart)]);
        }

        return $this->redirectToRoute('frontend_cart', ['locale' => $locale]);
    }
}
