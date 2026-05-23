<?php

declare(strict_types=1);

namespace Aurora\Module\Welding\PdfTemplate\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Welding\Enum\WeldingPdfTemplateStatusEnum;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplate;
use Aurora\Module\Welding\PdfTemplate\Entity\WeldingPdfTemplateInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<WeldingPdfTemplateInterface> */
class WeldingPdfTemplateRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeldingPdfTemplate::class, WeldingPdfTemplateInterface::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?WeldingPdfTemplateStatusEnum $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', Order::Descending->value);
        $countQb = $this->createQueryBuilder('t')->select('COUNT(t.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(t.name) LIKE :search')->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(t.name) LIKE :search')->setParameter('search', $pattern);
        }

        if ($status instanceof WeldingPdfTemplateStatusEnum) {
            $qb->andWhere('t.status = :status')->setParameter('status', $status);
            $countQb->andWhere('t.status = :status')->setParameter('status', $status);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /** @return list<WeldingPdfTemplateInterface> */
    public function findActive(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('status', WeldingPdfTemplateStatusEnum::Active)
            ->orderBy('t.name', Order::Ascending->value)
            ->getQuery()
            ->getResult();
    }
}
