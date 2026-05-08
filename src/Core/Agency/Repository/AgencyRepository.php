<?php

declare(strict_types=1);

namespace Aurora\Core\Agency\Repository;

use Aurora\Core\Agency\Entity\Agency;
use Aurora\Core\Agency\Entity\AgencyInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AgencyInterface>
 */
class AgencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, self::resolveEntityClass($registry));
    }

    /**
     * Resolves AgencyInterface to its concrete class via Doctrine's
     * resolve_target_entities config. Without this, the constructor would
     * hardcode Aurora's Agency::class and clients overriding AgencyInterface
     * would still see queries hit core_agencies.
     */
    private static function resolveEntityClass(ManagerRegistry $registry): string
    {
        $manager = $registry->getManagerForClass(Agency::class);
        if (null === $manager) {
            return Agency::class;
        }

        return $manager->getClassMetadata(AgencyInterface::class)->getName();
    }

    /** @return AgencyInterface[] */
    public function findAllAlphabetical(): array
    {
        return $this->createQueryBuilder('agency')
            ->orderBy('agency.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
