<?php

declare(strict_types=1);

namespace Aurora\Core\Configuration\Theme\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Configuration\Theme\Entity\Theme;
use Aurora\Core\Configuration\Theme\Entity\ThemeInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<ThemeInterface>
 */
class ThemeRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Theme::class, ThemeInterface::class);
    }

    public function findActive(): ?ThemeInterface
    {
        return $this->findOneBy(['active' => true]);
    }

    public function findBySlug(string $slug): ?ThemeInterface
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
