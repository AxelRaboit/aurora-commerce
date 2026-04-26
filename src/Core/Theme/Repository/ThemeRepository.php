<?php

declare(strict_types=1);

namespace App\Core\Theme\Repository;

use App\Core\Theme\Entity\Theme;
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

    public function findActive(): ?Theme
    {
        return $this->findOneBy(['active' => true]);
    }

    public function findBySlug(string $slug): ?Theme
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
