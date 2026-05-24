<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Document\Entity\DocumentVersion;
use Aurora\Module\Ged\Document\Entity\DocumentVersionInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<DocumentVersionInterface> */
class DocumentVersionRepository extends ResolveTargetEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentVersion::class, DocumentVersionInterface::class);
    }

    /** @return list<DocumentVersionInterface> */
    public function findByDocument(DocumentInterface $document): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.document = :doc')
            ->setParameter('doc', $document)
            ->orderBy('v.versionNumber', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }

    public function getNextVersionNumber(DocumentInterface $document): int
    {
        $max = $this->createQueryBuilder('v')
            ->select('MAX(v.versionNumber)')
            ->andWhere('v.document = :doc')
            ->setParameter('doc', $document)
            ->getQuery()
            ->getSingleScalarResult();

        return null !== $max ? (int) $max + 1 : 1;
    }
}
