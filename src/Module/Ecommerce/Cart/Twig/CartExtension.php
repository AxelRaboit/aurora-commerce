<?php

declare(strict_types=1);

namespace App\Module\Ecommerce\Cart\Twig;

use App\Module\Ecommerce\Cart\Contract\CartManagerInterface;
use App\Module\Ecommerce\Cart\Entity\Cart;
use App\Module\Ecommerce\Service\EcommerceContext;
use Twig\Attribute\AsTwigFunction;

final readonly class CartExtension
{
    public function __construct(
        private CartManagerInterface $cartManager,
        private EcommerceContext $ecommerceContext,
    ) {}

    /**
     * Returns the total number of items in the current user/session cart.
     * Returns 0 if no cart yet (does not create one — read-only) or if ecommerce front is disabled.
     */
    #[AsTwigFunction(name: 'cart_count')]
    public function getCartCount(): int
    {
        if (!$this->ecommerceContext->isFrontEnabled()) {
            return 0;
        }

        $cart = $this->cartManager->getCurrentCart(false);

        return $cart instanceof Cart ? $cart->getTotalQuantity() : 0;
    }

    /** Whether the ecommerce front is enabled — for templates to conditionally render shop/cart entries. */
    #[AsTwigFunction(name: 'is_ecommerce_front_enabled')]
    public function isEcommerceFrontEnabled(): bool
    {
        return $this->ecommerceContext->isFrontEnabled();
    }
}
