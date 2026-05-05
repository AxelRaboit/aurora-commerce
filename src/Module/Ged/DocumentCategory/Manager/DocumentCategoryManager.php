<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentCategory\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Module\Ged\DocumentCategory\Contract\DocumentCategoryManagerInterface;
use Aurora\Module\Ged\DocumentCategory\DTO\DocumentCategoryInput;
use Aurora\Module\Ged\DocumentCategory\Entity\DocumentCategory;
use Aurora\Module\Ged\DocumentCategory\Repository\DocumentCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsAlias(DocumentCategoryManagerInterface::class)]
final readonly class DocumentCategoryManager implements DocumentCategoryManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentCategoryRepository $categoryRepository,
        private AuditLogger $auditLogger,
    ) {}

    public function create(DocumentCategoryInput $input): DocumentCategory
    {
        $category = new DocumentCategory();
        $this->applyInput($category, $input);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->auditLogger->log('ged', 'category.created', 'DocumentCategory', $category->getId(), ['name' => $category->getName()]);

        return $category;
    }

    public function update(DocumentCategory $category, DocumentCategoryInput $input): void
    {
        $this->applyInput($category, $input);
        $this->entityManager->flush();

        $this->auditLogger->log('ged', 'category.updated', 'DocumentCategory', $category->getId(), ['name' => $category->getName()]);
    }

    public function delete(DocumentCategory $category): void
    {
        $name = $category->getName();
        $id = $category->getId();

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        $this->auditLogger->log('ged', 'category.deleted', 'DocumentCategory', $id, ['name' => $name]);
    }

    private function applyInput(DocumentCategory $category, DocumentCategoryInput $input): void
    {
        $category->setName($input->name);
        $category->setDescription($input->description);
        $category->setSlug($this->uniqueSlug($input->name, $category->getId()));
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
