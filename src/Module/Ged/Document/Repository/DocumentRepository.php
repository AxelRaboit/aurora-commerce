<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Repository;

use Aurora\Core\Repository\ResolveTargetEntityRepository;
use Aurora\Core\Repository\Trait\PaginationTrait;
use Aurora\Module\Ged\Document\Entity\Document;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Enum\DocumentStatusEnum;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ResolveTargetEntityRepository<DocumentInterface> */
class DocumentRepository extends ResolveTargetEntityRepository
{
    use PaginationTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class, DocumentInterface::class);
    }

    public function findPaginated(
        int $page,
        int $limit = 20,
        ?string $search = null,
        ?int $categoryId = null,
        ?int $tagId = null,
        ?int $folderId = null,
        ?DocumentStatusEnum $status = null,
    ): array {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.category', 'c')
            ->leftJoin('d.folder', 'folder')
            ->addSelect('c', 'folder')
            ->orderBy('d.createdAt', Order::Descending->value);
        $countQb = $this->createQueryBuilder('d')->select('COUNT(d.id)');

        if (null !== $search && '' !== $search) {
            $pattern = '%'.mb_strtolower($search).'%';
            $qb->andWhere('LOWER(d.title) LIKE :search OR LOWER(d.reference) LIKE :search')->setParameter('search', $pattern);
            $countQb->andWhere('LOWER(d.title) LIKE :search OR LOWER(d.reference) LIKE :search')->setParameter('search', $pattern);
        }

        if (null !== $categoryId) {
            $qb->andWhere('d.category = :cat')->setParameter('cat', $categoryId);
            $countQb->andWhere('d.category = :cat')->setParameter('cat', $categoryId);
        }

        if (null !== $tagId) {
            $qb->innerJoin('d.tags', 'tagFilter')->andWhere('tagFilter.id = :tagId')->setParameter('tagId', $tagId);
            $countQb->innerJoin('d.tags', 'tagFilter')->andWhere('tagFilter.id = :tagId')->setParameter('tagId', $tagId);
        }

        if (null !== $folderId) {
            $qb->andWhere('d.folder = :folder')->setParameter('folder', $folderId);
            $countQb->andWhere('d.folder = :folder')->setParameter('folder', $folderId);
        }

        if ($status instanceof DocumentStatusEnum) {
            $qb->andWhere('d.status = :status')->setParameter('status', $status);
            $countQb->andWhere('d.status = :status')->setParameter('status', $status);
        }

        $result = $this->paginate($qb, $countQb, $page, $limit);
        $this->hydrateDocumentTags($result['items']);

        return $result;
    }

    /**
     * Batch-loads the tags collection for a page of documents to avoid N+1
     * (ManyToMany cannot be joined alongside a LIMIT query).
     *
     * @param list<Document> $documents
     */
    private function hydrateDocumentTags(array $documents): void
    {
        if ([] === $documents) {
            return;
        }

        $ids = array_map(static fn (Document $document): int => $document->getId(), $documents);

        $this->createQueryBuilder('d')
            ->leftJoin('d.tags', 'tag')
            ->addSelect('tag')
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
