<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\View;

use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Theme\Service\ThemeContext;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Serializer\CartSerializer;

/**
 * Builds the Twig payload for the front cart view. Centralises serialisation
 * and shared front context wiring so the controller stays focused on flow.
 */
final readonly class CartViewBuilder
{
    public function __construct(
        private CartSerializer $cartSerializer,
        private FrontContext $frontContext,
        private ThemeContext $themeContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(Cart $cart, string $locale): array
    {
        return [
            'cart' => $this->cartSerializer->serialize($cart),
            'locale' => $locale,
            'context' => $this->frontContext,
            'showFrontMenus' => true,
            'themeContext' => $this->themeContext,
        ];
    }
}
