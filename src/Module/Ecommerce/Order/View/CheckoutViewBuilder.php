<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Order\View;

use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Locale\Enum\CountryEnum;
use Aurora\Core\Theme\Service\ThemeContext;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Serializer\CartSerializer;
use Aurora\Module\Ecommerce\Order\Entity\Order;
use Aurora\Module\Ecommerce\Order\Serializer\OrderSerializer;

/**
 * Builds the Twig payloads for the front checkout flow.
 */
final readonly class CheckoutViewBuilder
{
    public function __construct(
        private CartSerializer $cartSerializer,
        private OrderSerializer $orderSerializer,
        private FrontContext $frontContext,
        private ThemeContext $themeContext,
    ) {}

    /**
     * @param array<string, mixed> $formData
     *
     * @return array<string, mixed>
     */
    public function checkoutView(Cart $cart, bool $cartRequiresShipping, array $formData, string $locale, string $stripePublicKey, string $submitPath): array
    {
        return [
            'cart' => $this->cartSerializer->serialize($cart),
            'initialForm' => $formData,
            'requiresShipping' => $cartRequiresShipping,
            'countries' => CountryEnum::options($locale),
            'stripePublicKey' => $stripePublicKey,
            'submitPath' => $submitPath,
            'locale' => $locale,
            'context' => $this->frontContext,
            'themeContext' => $this->themeContext,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showView(Order $order, string $locale): array
    {
        return [
            'order' => $this->orderSerializer->serialize($order),
            'locale' => $locale,
            'context' => $this->frontContext,
            'themeContext' => $this->themeContext,
        ];
    }
}
