<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultEntry\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Vault\VaultEntry\Dto\VaultEntryInputInterface;
use Aurora\Module\Vault\VaultEntry\Entity\VaultEntry;
use Aurora\Module\Vault\VaultEntry\Entity\VaultEntryInterface;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolderInterface;
use Aurora\Module\Vault\VaultFolder\Repository\VaultFolderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VaultEntryManagerInterface::class)]
class VaultEntryManager implements VaultEntryManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly VaultFolderRepository $vaultFolderRepository,
    ) {}

    public function create(CoreUserInterface $user, VaultEntryInputInterface $input): VaultEntryInterface
    {
        $entry = $this->createVaultEntry();
        $entry->setUser($user);
        $this->applyInput($entry, $input);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        $this->auditCreated($entry);

        return $entry;
    }

    public function update(VaultEntryInterface $entry, VaultEntryInputInterface $input): void
    {
        $this->applyInput($entry, $input);
        $this->entityManager->flush();

        $this->auditUpdated($entry);
    }

    public function delete(VaultEntryInterface $entry): void
    {
        $this->auditDeleted($entry);

        $this->entityManager->remove($entry);
        $this->entityManager->flush();
    }

    public function toggleFavorite(VaultEntryInterface $entry): void
    {
        $entry->setIsFavorite(!$entry->isFavorite());
        $this->entityManager->flush();
    }

    public function move(VaultEntryInterface $entry, ?VaultFolderInterface $folder): void
    {
        $entry->setFolder($folder);
        $this->entityManager->flush();
    }

    protected function createVaultEntry(): VaultEntryInterface
    {
        return new VaultEntry();
    }

    protected function applyInput(VaultEntryInterface $entry, VaultEntryInputInterface $input): void
    {
        $entry->setType($input->getType());
        $entry->setTitle($input->getTitle());
        $entry->setUrl($input->getUrl());
        $entry->setEncryptedData($input->getEncryptedData());
        $entry->setIv($input->getIv());
        $entry->setIsFavorite($input->isFavorite());

        $folder = null;
        if (null !== $input->getFolderId()) {
            $folder = $this->vaultFolderRepository->findOneByUserAndId($entry->getUser(), $input->getFolderId());
        }

        $entry->setFolder($folder);
    }

    protected function auditCreated(VaultEntryInterface $entry): void
    {
        $this->auditLogger->log('vault', 'entry.created', 'VaultEntry', $entry->getId(), $this->auditPayload($entry));
    }

    protected function auditUpdated(VaultEntryInterface $entry): void
    {
        $this->auditLogger->log('vault', 'entry.updated', 'VaultEntry', $entry->getId(), $this->auditPayload($entry));
    }

    protected function auditDeleted(VaultEntryInterface $entry): void
    {
        $this->auditLogger->log('vault', 'entry.deleted', 'VaultEntry', $entry->getId(), $this->auditPayload($entry));
    }

    protected function auditPayload(VaultEntryInterface $entry): array
    {
        return ['title' => $entry->getTitle(), 'type' => $entry->getType()->value];
    }
}
