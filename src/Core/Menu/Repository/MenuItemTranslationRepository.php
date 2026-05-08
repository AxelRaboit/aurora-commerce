<?php

declare(strict_types=1);

namespace Aurora\Core\Menu\Repository;

use Aurora\Core\Menu\Entity\MenuItemTranslation;
use Aurora\Core\Menu\Entity\MenuItemTranslationInterface;
use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<MenuItemTranslationInterface>
 */
class MenuItemTranslationRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuItemTranslation::class, MenuItemTranslationInterface::class);
    }
}
