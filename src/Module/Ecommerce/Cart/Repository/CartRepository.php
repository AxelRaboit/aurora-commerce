<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Repository;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<CartInterface> */
class CartRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class, CartInterface::class);
    }

    public function findOneBySession(string $sessionId): ?Cart
    {
        return $this->findOneBy(['sessionId' => $sessionId]);
    }

    public function findOneByUser(User $user): ?Cart
    {
        return $this->findOneBy(['user' => $user]);
    }
}
