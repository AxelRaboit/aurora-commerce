<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\ContactTag\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Crm\ContactTag\Entity\ContactTag;
use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResolveTargetEntityRepository<ContactTagInterface>
 */
class ContactTagRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactTag::class, ContactTagInterface::class);
    }

    /** @return list<ContactTagInterface> */
    public function findAllOrdered(): array
    {
        /** @var list<ContactTagInterface> $contactTags */
        $contactTags = $this->createQueryBuilder('t')
            ->orderBy('LOWER(t.label)', 'ASC')
            ->getQuery()
            ->getResult();

        return $contactTags;
    }

    public function findOneBySlug(string $slug): ?ContactTagInterface
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * @param list<int> $ids
     *
     * @return list<ContactTagInterface>
     */
    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        /** @var list<ContactTagInterface> $contactTags */
        $contactTags = $this->findBy(['id' => $ids]);

        return $contactTags;
    }
}
