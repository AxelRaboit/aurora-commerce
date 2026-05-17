<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\View;

use Aurora\Core\Frontend\Service\Context;
use Aurora\Module\Configuration\Theme\Service\ThemeContext;
use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;
use Aurora\Module\Ecommerce\Cart\Serializer\CartSerializer;

/**
 * Builds the Twig payload for the front cart view. Centralises serialisation
 * and shared front context wiring so the controller stays focused on flow.
 */
final readonly class CartViewBuilder
{
    public function __construct(
        private CartSerializer $cartSerializer,
        private Context $context,
        private ThemeContext $themeContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function indexView(?CartInterface $cart, string $locale): array
    {
        return [
            'cart' => $cart instanceof CartInterface ? $this->cartSerializer->serialize($cart) : null,
            'locale' => $locale,
            'context' => $this->context,
            'showFrontMenus' => true,
            'themeContext' => $this->themeContext,
        ];
    }
}
