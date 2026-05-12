<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentFolder\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Module\Ged\DocumentFolder\Dto\DocumentFolderInputInterface;
use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolder;
use Aurora\Module\Ged\DocumentFolder\Entity\DocumentFolderInterface;
use Aurora\Module\Ged\DocumentFolder\Repository\DocumentFolderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DocumentFolderManagerInterface::class)]
class DocumentFolderManager implements DocumentFolderManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly DocumentFolderRepository $folderRepository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(DocumentFolderInputInterface $input): DocumentFolderInterface
    {
        $folder = $this->createDocumentFolder();
        $this->applyInput($folder, $input);
        $this->entityManager->persist($folder);
        $this->entityManager->flush();

        $this->auditCreated($folder);

        return $folder;
    }

    public function update(DocumentFolderInterface $folder, DocumentFolderInputInterface $input): void
    {
        $this->applyInput($folder, $input);
        $this->entityManager->flush();

        $this->auditUpdated($folder);
    }

    public function delete(DocumentFolderInterface $folder): void
    {
        $this->auditDeleted($folder);

        $this->entityManager->remove($folder);
        $this->entityManager->flush();
    }

    protected function createDocumentFolder(): DocumentFolderInterface
    {
        return new DocumentFolder();
    }

    protected function applyInput(DocumentFolderInterface $folder, DocumentFolderInputInterface $input): void
    {
        $folder->setName($input->getName());
        $folder->setPosition($input->getPosition());
        $folder->setParent(null !== $input->getParentId() ? $this->folderRepository->find($input->getParentId()) : null);
    }

    protected function auditCreated(DocumentFolderInterface $folder): void
    {
        $this->auditLogger->log('ged', 'folder.created', 'DocumentFolder', $folder->getId(), $this->auditPayload($folder));
    }

    protected function auditUpdated(DocumentFolderInterface $folder): void
    {
        $this->auditLogger->log('ged', 'folder.updated', 'DocumentFolder', $folder->getId(), $this->auditPayload($folder));
    }

    protected function auditDeleted(DocumentFolderInterface $folder): void
    {
        $this->auditLogger->log('ged', 'folder.deleted', 'DocumentFolder', $folder->getId(), $this->auditPayload($folder));
    }

    protected function auditPayload(DocumentFolderInterface $folder): array
    {
        return ['name' => $folder->getName(), 'parentId' => $folder->getParent()?->getId()];
    }
}
