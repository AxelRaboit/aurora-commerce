<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Manager;

use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;
use Aurora\Module\Ecommerce\Cart\Entity\CartItem;
use Aurora\Module\Ecommerce\Cart\Entity\CartItemInterface;
use Aurora\Module\Ecommerce\Cart\Repository\CartRepository;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Ecommerce\Setting\EcommerceSettingEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsAlias(CartManagerInterface::class)]
class CartManager implements CartManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly CartRepository $cartRepository,
        protected readonly RequestStack $requestStack,
        protected readonly Security $security,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
    ) {}

    /**
     * Returns the current cart for the active user (logged) or session (anonymous).
     * Creates an empty one if none exists.
     */
    public function getCurrentCart(bool $createIfMissing = true): ?CartInterface
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $cart = $this->cartRepository->findOneByUser($user);
            if (!$cart instanceof CartInterface && $createIfMissing) {
                $cart = $this->createCart()->setUser($user);
                $this->entityManager->persist($cart);
                $this->entityManager->flush();
                $cartPrefix = $this->settingRepository->getOrDefault(EcommerceSettingEnum::CartPrefix);
                $cart->setReference($this->sequenceGenerator->next($cartPrefix));
                $this->entityManager->flush();
            }

            return $cart;
        }

        $sessionId = $this->getOrCreateSessionId();
        $cart = $this->cartRepository->findOneBySession($sessionId);
        if (!$cart instanceof CartInterface && $createIfMissing) {
            $cart = $this->createCart()->setSessionId($sessionId);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
            $cartPrefix = $this->settingRepository->getOrDefault(EcommerceSettingEnum::CartPrefix);
            $cart->setReference($this->sequenceGenerator->next($cartPrefix));
            $this->entityManager->flush();
        }

        return $cart;
    }

    public function addItem(ListingInterface $listing, int $quantity = 1): CartInterface
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
            $item = $this->createCartItem()
                ->setListing($listing)
                ->setQuantity(max(1, $quantity))
                ->setUnitPriceCents($product->getPriceCents() ?? 0)
                ->setCurrency($product->getCurrency());
            $cart->addItem($item);
            $this->entityManager->persist($item);
            $this->entityManager->flush();
            $itemPrefix = $this->settingRepository->getOrDefault(EcommerceSettingEnum::CartItemPrefix);
            $item->setReference($this->sequenceGenerator->next($itemPrefix));
        }

        $this->entityManager->flush();

        return $cart;
    }

    public function updateItemQuantity(ListingInterface $listing, int $quantity): CartInterface
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

    public function removeItem(ListingInterface $listing): CartInterface
    {
        return $this->updateItemQuantity($listing, 0);
    }

    public function clear(): void
    {
        $cart = $this->getCurrentCart(false);
        if (!$cart instanceof CartInterface) {
            return;
        }

        foreach ($cart->getItems() as $item) {
            $this->entityManager->remove($item);
        }

        $cart->getItems()->clear();
        $this->entityManager->flush();
    }

    protected function createCart(): CartInterface
    {
        return new Cart();
    }

    protected function createCartItem(): CartItemInterface
    {
        return new CartItem();
    }

    private function getOrCreateSessionId(): string
    {
        $session = $this->requestStack->getSession();
        $sessionId = $session->get('backend_ecommerce_cart_session');
        if (!is_string($sessionId) || '' === $sessionId) {
            $sessionId = bin2hex(random_bytes(16));
            $session->set('backend_ecommerce_cart_session', $sessionId);
        }

        return $sessionId;
    }
}
