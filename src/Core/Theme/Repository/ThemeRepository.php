<?php

declare(strict_types=1);

namespace Aurora\Core\Theme\Repository;

use Aurora\Core\Theme\Entity\Theme;
use Aurora\Core\Theme\Entity\ThemeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Theme>
 */
class ThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Theme::class);
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
