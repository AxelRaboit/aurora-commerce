<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Cart\Repository;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Ecommerce\Cart\Entity\Cart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Cart> */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
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
