<?php

declare(strict_types=1);

namespace App\Module\Ecommerce\Cart\Manager;

use App\Core\User\Entity\User;
use App\Module\Ecommerce\Cart\Contract\CartManagerInterface;
use App\Module\Ecommerce\Cart\Entity\Cart;
use App\Module\Ecommerce\Cart\Entity\CartItem;
use App\Module\Ecommerce\Cart\Repository\CartRepository;
use App\Module\Ecommerce\Listing\Entity\Listing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsAlias(CartManagerInterface::class)]
final readonly class CartManager implements CartManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CartRepository $cartRepository,
        private RequestStack $requestStack,
        private Security $security,
    ) {}

    /**
     * Returns the current cart for the active user (logged) or session (anonymous).
     * Creates an empty one if none exists.
     */
    public function getCurrentCart(bool $createIfMissing = true): ?Cart
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $cart = $this->cartRepository->findOneByUser($user);
            if (!$cart instanceof Cart && $createIfMissing) {
                $cart = (new Cart())->setUser($user);
                $this->entityManager->persist($cart);
                $this->entityManager->flush();
            }

            return $cart;
        }

        $sessionId = $this->getOrCreateSessionId();
        $cart = $this->cartRepository->findOneBySession($sessionId);
        if (!$cart instanceof Cart && $createIfMissing) {
            $cart = (new Cart())->setSessionId($sessionId);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
        }

        return $cart;
    }

    public function addItem(Listing $listing, int $quantity = 1): Cart
    {
        $cart = $this->getCurrentCart();
        $existing = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getListing()->getId() === $listing->getId()) {
                $existing = $item;
                break;
            }
        }

        if (null !== $existing) {
            $existing->setQuantity($existing->getQuantity() + max(1, $quantity));
        } else {
            $product = $listing->getProduct();
            $item = (new CartItem())
                ->setListing($listing)
                ->setQuantity(max(1, $quantity))
                ->setUnitPriceCents($product->getPriceCents() ?? 0)
                ->setCurrency($product->getCurrency());
            $cart->addItem($item);
            $this->entityManager->persist($item);
        }

        $this->entityManager->flush();

        return $cart;
    }

    public function updateItemQuantity(Listing $listing, int $quantity): Cart
    {
        $cart = $this->getCurrentCart();
        foreach ($cart->getItems() as $item) {
            if ($item->getListing()->getId() === $listing->getId()) {
                if ($quantity <= 0) {
                    $cart->removeItem($item);
                    $this->entityManager->remove($item);
                } else {
                    $item->setQuantity($quantity);
                }

                $this->entityManager->flush();

                return $cart;
            }
        }

        return $cart;
    }

    public function removeItem(Listing $listing): Cart
    {
        return $this->updateItemQuantity($listing, 0);
    }

    public function clear(): void
    {
        $cart = $this->getCurrentCart(false);
        if (!$cart instanceof Cart) {
            return;
        }

        foreach ($cart->getItems() as $item) {
            $this->entityManager->remove($item);
        }

        $cart->getItems()->clear();
        $this->entityManager->flush();
    }

    private function getOrCreateSessionId(): string
    {
        $session = $this->requestStack->getSession();
        $sessionId = $session->get('ecommerce_cart_session');
        if (!is_string($sessionId) || '' === $sessionId) {
            $sessionId = bin2hex(random_bytes(16));
            $session->set('ecommerce_cart_session', $sessionId);
        }

        return $sessionId;
    }
}
