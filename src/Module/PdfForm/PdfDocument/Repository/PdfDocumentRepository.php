<?php

declare(strict_types=1);

namespace Aurora\Module\PdfForm\PdfDocument\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocument;
use Aurora\Module\PdfForm\PdfDocument\Entity\PdfDocumentInterface;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<PdfDocumentInterface> */
class PdfDocumentRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PdfDocument::class, PdfDocumentInterface::class);
    }

    public function findPaginated(int $page, int $limit = 20, ?string $search = null, ?int $templateId = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.template', 't')
            ->addSelect('t')
            ->orderBy('d.createdAt', Order::Descending->value);
        $countQb = $this->createQueryBuilder('d')->select('COUNT(d.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(d.label) LIKE :search OR LOWER(d.reference) LIKE :search')->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(d.label) LIKE :search OR LOWER(d.reference) LIKE :search')->setParameter('search', $pattern);
        }

        if (null !== $templateId) {
            $qb->andWhere('t.id = :templateId')->setParameter('templateId', $templateId);
            $countQb->leftJoin('d.template', 't2')->andWhere('t2.id = :templateId')->setParameter('templateId', $templateId);
        }

        return $this->paginate($qb, $countQb, $page, $limit);
    }

    /** @return list<string> All non-null file paths stored in the database. */
    public function findAllFilePaths(): array
    {
        return array_column(
            $this->createQueryBuilder('d')
                ->select('d.filePath')
                ->where('d.filePath IS NOT NULL')
                ->getQuery()
                ->getScalarResult(),
            'filePath',
        );
    }
}
