<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Repository;

use Aurora\Core\Menu\Entity\Menu;
use Aurora\Core\Menu\Entity\MenuItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MenuItem>
 */
class MenuItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuItem::class);
    }

    /**
     * Return root items (parent = null) of a menu, ordered by position,
     * with children + translations eager-loaded.
     *
     * @return MenuItem[]
     */
    public function findRootItems(Menu $menu): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.children', 'c')->addSelect('c')
            ->leftJoin('i.translations', 't')->addSelect('t')
            ->leftJoin('c.translations', 'ct')->addSelect('ct')
            ->where('i.menu = :menu')
            ->andWhere('i.parent IS NULL')
            ->setParameter('menu', $menu)
            ->orderBy('i.position', Order::Ascending->value)
            ->addOrderBy('c.position', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
