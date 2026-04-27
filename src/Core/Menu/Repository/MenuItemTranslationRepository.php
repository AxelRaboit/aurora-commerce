<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Repository;

use Aurora\Core\Menu\Entity\MenuItemTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MenuItemTranslation>
 */
class MenuItemTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuItemTranslation::class);
    }
}
