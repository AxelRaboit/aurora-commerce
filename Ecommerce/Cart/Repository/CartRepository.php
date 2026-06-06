<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Aurora\Module\Ecommerce\Cart\Entity\CartInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<CartInterface> */
class CartRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class, CartInterface::class);
    }

    public function findOneBySession(string $sessionId): ?CartInterface
    {
        return $this->findOneBy(['sessionId' => $sessionId]);
    }

    public function findOneByUser(CoreUserInterface $user): ?CartInterface
    {
        return $this->findOneBy(['user' => $user]);
    }
}
