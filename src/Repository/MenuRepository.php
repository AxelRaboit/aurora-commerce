<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
            ->orderBy('m.location', 'ASC')
            ->addOrderBy('i.position', 'ASC')
            ->addOrderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($menus as $menu) {
            $indexed[$menu->getLocation()] = $menu;
        }

        return $indexed;
    }
}
