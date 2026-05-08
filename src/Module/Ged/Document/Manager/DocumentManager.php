<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Module\Ged\Document\Dto\DocumentInputInterface;
use Aurora\Module\Ged\Document\Entity\Document;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\DocumentCategory\Repository\DocumentCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DocumentManagerInterface::class)]
class DocumentManager implements DocumentManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly DocumentCategoryRepository $categoryRepository,
        protected readonly MediaRepository $mediaRepository,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(DocumentInputInterface $input): DocumentInterface
    {
        $document = $this->createDocument();
        $document->setReference($this->sequenceGenerator->next(ApplicationParameterEnum::GedDocumentPrefix->value));
        $this->applyInput($document, $input);
        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $this->auditCreated($document);

        return $document;
    }

    public function update(DocumentInterface $document, DocumentInputInterface $input): void
    {
        $this->applyInput($document, $input);
        $this->entityManager->flush();

        $this->auditUpdated($document);
    }

    public function delete(DocumentInterface $document): void
    {
        $this->auditDeleted($document);

        $this->entityManager->remove($document);
        $this->entityManager->flush();
    }

    protected function createDocument(): DocumentInterface
    {
        return new Document();
    }

    protected function applyInput(DocumentInterface $document, DocumentInputInterface $input): void
    {
        $document->setTitle($input->getTitle());
        $document->setDescription($input->getDescription());
        $document->setStatus($input->getStatus());
        $document->setCategory(null !== $input->getCategoryId() ? $this->categoryRepository->find($input->getCategoryId()) : null);
        $document->setFile(null !== $input->getFileId() ? $this->mediaRepository->find($input->getFileId()) : null);
    }

    protected function auditCreated(DocumentInterface $document): void
    {
        $this->auditLogger->log('ged', 'document.created', 'Document', $document->getId(), $this->auditPayload($document));
    }

    protected function auditUpdated(DocumentInterface $document): void
    {
        $this->auditLogger->log('ged', 'document.updated', 'Document', $document->getId(), $this->auditPayload($document));
    }

    protected function auditDeleted(DocumentInterface $document): void
    {
        $this->auditLogger->log('ged', 'document.deleted', 'Document', $document->getId(), $this->auditPayload($document));
    }

    protected function auditPayload(DocumentInterface $document): array
    {
        return ['title' => $document->getTitle(), 'reference' => $document->getReference()];
    }
}
