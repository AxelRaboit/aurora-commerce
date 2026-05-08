<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Module\Ged\Document\Contract\DocumentManagerInterface;
use Aurora\Module\Ged\Document\Dto\DocumentInput;
use Aurora\Module\Ged\Document\Entity\Document;
use Aurora\Module\Ged\DocumentCategory\Repository\DocumentCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DocumentManagerInterface::class)]
final readonly class DocumentManager implements DocumentManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentCategoryRepository $categoryRepository,
        private MediaRepository $mediaRepository,
        private SequenceGenerator $sequenceGenerator,
        private AuditLogger $auditLogger,
    ) {}

    public function create(DocumentInput $input): Document
    {
        $document = new Document();
        $document->setReference($this->sequenceGenerator->next(ApplicationParameterEnum::GedDocumentPrefix->value));
        $this->applyInput($document, $input);
        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $this->auditLogger->log('ged', 'document.created', 'Document', $document->getId(), ['title' => $document->getTitle()]);

        return $document;
    }

    public function update(Document $document, DocumentInput $input): void
    {
        $this->applyInput($document, $input);
        $this->entityManager->flush();

        $this->auditLogger->log('ged', 'document.updated', 'Document', $document->getId(), ['title' => $document->getTitle()]);
    }

    public function delete(Document $document): void
    {
        $title = $document->getTitle();
        $id = $document->getId();

        $this->entityManager->remove($document);
        $this->entityManager->flush();

        $this->auditLogger->log('ged', 'document.deleted', 'Document', $id, ['title' => $title]);
    }

    private function applyInput(Document $document, DocumentInput $input): void
    {
        $document->setTitle($input->title);
        $document->setDescription($input->description);
        $document->setStatus($input->status);
        $document->setCategory(null !== $input->categoryId ? $this->categoryRepository->find($input->categoryId) : null);
        $document->setFile(null !== $input->fileId ? $this->mediaRepository->find($input->fileId) : null);
    }
}
