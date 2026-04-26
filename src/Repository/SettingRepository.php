<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Setting;
use App\Enum\ApplicationParameter\ApplicationParameterEnum;
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

    /** @var array<string, string|null>|null */
    private ?array $cache = null;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $this->warmUp();

        return array_key_exists($key, $this->cache) ? ($this->cache[$key] ?? $default) : $default;
    }

    /**
     * Returns the stored value for the given parameter, falling back to its
     * declared default when missing or null. Always returns a non-null string.
     */
    public function getOrDefault(ApplicationParameterEnum $parameter): string
    {
        $default = $parameter->getDefaultValue();

        return $this->get($parameter->value, $default) ?? $default;
    }

    public function getBoolean(string $key, bool $default = false): bool
    {
        return '1' === $this->get($key, $default ? '1' : '0');
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

        if (null !== $this->cache) {
            $this->cache[$key] = $value;
        }
    }

    private function warmUp(): void
    {
        if (null !== $this->cache) {
            return;
        }

        $this->cache = [];
        foreach ($this->findAll() as $setting) {
            $this->cache[$setting->getKey()] = $setting->getValue();
        }
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
