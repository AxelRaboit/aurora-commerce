<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Ged\Document\Dto\DocumentInputInterface;
use Aurora\Module\Ged\Document\Entity\Document;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Document\Entity\DocumentVersion;
use Aurora\Module\Ged\Document\Entity\DocumentVersionInterface;
use Aurora\Module\Ged\Document\Repository\DocumentVersionRepository;
use Aurora\Module\Ged\DocumentCategory\Repository\DocumentCategoryRepository;
use Aurora\Module\Ged\DocumentFolder\Repository\DocumentFolderRepository;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTagInterface;
use Aurora\Module\Ged\DocumentTag\Repository\DocumentTagRepository;
use Aurora\Module\Ged\Setting\GedSettingEnum;
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
        protected readonly SettingRepository $settingRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly DocumentTagRepository $tagRepository,
        protected readonly DocumentFolderRepository $folderRepository,
        protected readonly DocumentVersionRepository $versionRepository,
    ) {}

    public function create(DocumentInputInterface $input): DocumentInterface
    {
        $document = $this->createDocument();
        $prefix = $this->settingRepository->getOrDefault(GedSettingEnum::DocumentPrefix);
        $document->setReference($this->sequenceGenerator->next($prefix));
        $this->applyInput($document, $input);
        $this->entityManager->persist($document);
        $this->entityManager->flush();

        if ($document->getFile() instanceof MediaInterface) {
            $this->recordVersion($document);
        }

        $this->auditCreated($document);

        return $document;
    }

    public function update(DocumentInterface $document, DocumentInputInterface $input): void
    {
        $newFileId = $input->getFileId();
        $currentFileId = $document->getFile()?->getId();
        $fileChanged = null !== $newFileId && $newFileId !== $currentFileId;

        $this->applyInput($document, $input);
        $this->entityManager->flush();

        if ($fileChanged) {
            $this->recordVersion($document);
        }

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

    protected function createDocumentVersion(): DocumentVersionInterface
    {
        return new DocumentVersion();
    }

    protected function recordVersion(DocumentInterface $document): void
    {
        $version = $this->createDocumentVersion();
        $version->setDocument($document)
            ->setFile($document->getFile())
            ->setVersionNumber($this->versionRepository->getNextVersionNumber($document));
        $this->entityManager->persist($version);
        $this->entityManager->flush();
    }

    protected function applyInput(DocumentInterface $document, DocumentInputInterface $input): void
    {
        $document->setTitle($input->getTitle());
        $document->setDescription($input->getDescription());
        $document->setStatus($input->getStatus());
        $document->setCategory(null !== $input->getCategoryId() ? $this->categoryRepository->find($input->getCategoryId()) : null);
        $document->setFile(null !== $input->getFileId() ? $this->mediaRepository->find($input->getFileId()) : null);

        $document->clearTags();
        foreach ($input->getTagIds() as $tagId) {
            $tag = $this->tagRepository->find($tagId);
            if ($tag instanceof DocumentTagInterface) {
                $document->addTag($tag);
            }
        }

        $document->setFolder(null !== $input->getFolderId() ? $this->folderRepository->find($input->getFolderId()) : null);
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
