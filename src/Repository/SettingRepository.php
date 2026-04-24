<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Setting;
use App\Repository\Trait\PaginationTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Setting>
 */
class SettingRepository extends ServiceEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $setting = $this->find($key);

        return $setting?->getValue() ?? $default;
    }

    public function set(string $key, ?string $value): void
    {
        $setting = $this->find($key);

        if (!$setting instanceof Setting) {
            $setting = new Setting($key, $value);
            $this->getEntityManager()->persist($setting);
        } else {
            $setting->setValue($value);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @return Setting[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.group', Order::Ascending->value)
            ->addOrderBy('s.key', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{items: Setting[], total: int, page: int, totalPages: int}
     */
    public function findPaginated(int $page, int $limit = 20): array
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->orderBy('s.group', Order::Ascending->value)
            ->addOrderBy('s.key', Order::Ascending->value);
        $countQueryBuilder = $this->createQueryBuilder('s')->select('COUNT(s.key)');

        return $this->paginate($queryBuilder, $countQueryBuilder, $page, $limit);
    }
}
