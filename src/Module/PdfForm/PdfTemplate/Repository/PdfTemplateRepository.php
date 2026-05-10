<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfTemplate\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\PdfForm\Enum\PdfTemplateStatusEnum;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplate;
use Aurora\Module\PdfForm\PdfTemplate\Entity\PdfTemplateInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PdfTemplateInterface> */
class PdfTemplateRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PdfTemplate::class, PdfTemplateInterface::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?PdfTemplateStatusEnum $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', Order::Descending->value);
        $countQb = $this->createQueryBuilder('t')->select('COUNT(t.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(t.name) LIKE :search')->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(t.name) LIKE :search')->setParameter('search', $pattern);
        }

        if (null !== $status) {
            $qb->andWhere('t.status = :status')->setParameter('status', $status);
            $countQb->andWhere('t.status = :status')->setParameter('status', $status);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /** @return list<PdfTemplateInterface> */
    public function findActive(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('status', PdfTemplateStatusEnum::Active)
            ->orderBy('t.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
