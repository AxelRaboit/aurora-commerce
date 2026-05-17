<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultFolder\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Vault\VaultFolder\Dto\VaultFolderInputInterface;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolder;
use Aurora\Module\Vault\VaultFolder\Entity\VaultFolderInterface;
use Aurora\Module\Vault\VaultFolder\Repository\VaultFolderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VaultFolderManagerInterface::class)]
class VaultFolderManager implements VaultFolderManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly VaultFolderRepository $vaultFolderRepository,
    ) {}

    public function create(CoreUserInterface $user, VaultFolderInputInterface $input): VaultFolderInterface
    {
        $folder = $this->createVaultFolder();
        $folder->setUser($user);
        $this->applyInput($folder, $input);
        $folder->setPosition($this->vaultFolderRepository->countNextPositionForParent($user, $folder->getParent()));

        $this->entityManager->persist($folder);
        $this->entityManager->flush();

        $this->auditCreated($folder);

        return $folder;
    }

    public function update(VaultFolderInterface $folder, VaultFolderInputInterface $input): void
    {
        $this->applyInput($folder, $input);
        $this->entityManager->flush();

        $this->auditUpdated($folder);
    }

    public function delete(VaultFolderInterface $folder): void
    {
        $this->auditDeleted($folder);

        $this->entityManager->remove($folder);
        $this->entityManager->flush();
    }

    protected function createVaultFolder(): VaultFolderInterface
    {
        return new VaultFolder();
    }

    protected function applyInput(VaultFolderInterface $folder, VaultFolderInputInterface $input): void
    {
        $folder->setName($input->getName());
        $folder->setColor($input->getColor());
        $folder->setPosition($input->getPosition());

        $parent = null;
        if (null !== $input->getParentId()) {
            $parent = $this->vaultFolderRepository->findOneByUserAndId($folder->getUser(), $input->getParentId());
        }

        $folder->setParent($parent);
    }

    protected function auditCreated(VaultFolderInterface $folder): void
    {
        $this->auditLogger->log('vault', 'folder.created', 'VaultFolder', $folder->getId(), $this->auditPayload($folder));
    }

    protected function auditUpdated(VaultFolderInterface $folder): void
    {
        $this->auditLogger->log('vault', 'folder.updated', 'VaultFolder', $folder->getId(), $this->auditPayload($folder));
    }

    protected function auditDeleted(VaultFolderInterface $folder): void
    {
        $this->auditLogger->log('vault', 'folder.deleted', 'VaultFolder', $folder->getId(), $this->auditPayload($folder));
    }

    protected function auditPayload(VaultFolderInterface $folder): array
    {
        return ['name' => $folder->getName()];
    }
}
