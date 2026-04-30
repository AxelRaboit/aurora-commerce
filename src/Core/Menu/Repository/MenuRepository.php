<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Repository;

use Aurora\Core\Menu\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menu>
 */
class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    public function findByLocation(string $location): ?Menu
    {
        return $this->findOneBy(['location' => $location]);
    }

    /**
     * Loads all menus with items, children and translations in one query.
     * Keyed by location for O(1) lookup.
     *
     * @return array<string, Menu>
     */
    public function findAllWithItemsKeyedByLocation(): array
    {
        $menus = $this->createQueryBuilder('m')
            ->leftJoin('m.items', 'i')->addSelect('i')
            ->leftJoin('i.translations', 't')->addSelect('t')
            ->leftJoin('i.children', 'c')->addSelect('c')
            ->leftJoin('c.translations', 'ct')->addSelect('ct')
            ->orderBy('m.location', Order::Ascending->value)
            ->addOrderBy('i.position', Order::Ascending->value)
            ->addOrderBy('c.position', Order::Ascending->value)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($menus as $menu) {
            $indexed[$menu->getLocation()] = $menu;
        }

        return $indexed;
    }
}
