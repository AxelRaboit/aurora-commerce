<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentCategory\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Ged\DocumentCategory\Dto\DocumentCategoryInputInterface;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategory;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategoryInterface;
use Aurora\Module\Ged\DocumentCategory\Repository\DocumentCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsAlias(DocumentCategoryManagerInterface::class)]
class DocumentCategoryManager implements DocumentCategoryManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly DocumentCategoryRepository $categoryRepository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(DocumentCategoryInputInterface $input): DocumentCategoryInterface
    {
        $category = $this->createDocumentCategory();
        $this->applyInput($category, $input);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->auditCreated($category);

        return $category;
    }

    public function update(DocumentCategoryInterface $category, DocumentCategoryInputInterface $input): void
    {
        $this->applyInput($category, $input);
        $this->entityManager->flush();

        $this->auditUpdated($category);
    }

    public function delete(DocumentCategoryInterface $category): void
    {
        $this->auditDeleted($category);

        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    protected function createDocumentCategory(): DocumentCategoryInterface
    {
        return new DocumentCategory();
    }

    protected function applyInput(DocumentCategoryInterface $category, DocumentCategoryInputInterface $input): void
    {
        $category->setName($input->getName());
        $category->setDescription($input->getDescription());
        $category->setSlug($this->uniqueSlug($input->getName(), $category->getId()));
    }

    protected function auditCreated(DocumentCategoryInterface $category): void
    {
        $this->auditLogger->log('ged', 'category.created', 'DocumentCategory', $category->getId(), $this->auditPayload($category));
    }

    protected function auditUpdated(DocumentCategoryInterface $category): void
    {
        $this->auditLogger->log('ged', 'category.updated', 'DocumentCategory', $category->getId(), $this->auditPayload($category));
    }

    protected function auditDeleted(DocumentCategoryInterface $category): void
    {
        $this->auditLogger->log('ged', 'category.deleted', 'DocumentCategory', $category->getId(), $this->auditPayload($category));
    }

    protected function auditPayload(DocumentCategoryInterface $category): array
    {
        return ['name' => $category->getName()];
    }

    private function uniqueSlug(string $name, ?int $excludeId): string
    {
        $base = mb_strtolower(new AsciiSlugger()->slug($name)->toString());
        $slug = $base;
        $i = 2;
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $excludeId): bool
    {
        $qb = $this->categoryRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.slug = :slug')
            ->setParameter('slug', $slug);

        if (null !== $excludeId) {
            $qb->andWhere('c.id != :id')->setParameter('id', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
