<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultUserConfig\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Vault\VaultEntry\Entity\VaultEntryInterface;
use Aurora\Module\Vault\VaultEntry\Repository\VaultEntryRepository;
use Aurora\Module\Vault\VaultFolder\Repository\VaultFolderRepository;
use Aurora\Module\Vault\VaultUserConfig\Dto\VaultUserConfigInputInterface;
use Aurora\Module\Vault\VaultUserConfig\Entity\VaultUserConfig;
use Aurora\Module\Vault\VaultUserConfig\Entity\VaultUserConfigInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VaultUserConfigManagerInterface::class)]
class VaultUserConfigManager implements VaultUserConfigManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly VaultEntryRepository $vaultEntryRepository,
        protected readonly VaultFolderRepository $vaultFolderRepository,
    ) {}

    public function setup(CoreUserInterface $user, VaultUserConfigInputInterface $input): VaultUserConfigInterface
    {
        $config = $this->createVaultUserConfig();
        $config->setUser($user);
        $this->applyInput($config, $input);

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $this->auditSetup($config);

        return $config;
    }

    protected function createVaultUserConfig(): VaultUserConfigInterface
    {
        return new VaultUserConfig();
    }

    protected function applyInput(VaultUserConfigInterface $config, VaultUserConfigInputInterface $input): void
    {
        $config->setArgon2Salt($input->getArgon2Salt());
    }

    public function changeMasterPassword(VaultUserConfigInterface $config, string $newSalt, array $reEncryptedEntries): void
    {
        $config->setArgon2Salt($newSalt);

        foreach ($reEncryptedEntries as $entryData) {
            $entry = $this->vaultEntryRepository->findOneByUserAndId($config->getUser(), (int) $entryData['id']);
            if (!$entry instanceof VaultEntryInterface) {
                continue;
            }

            $entry->setEncryptedData($entryData['encryptedData']);
            $entry->setIv($entryData['iv']);
        }

        $this->entityManager->flush();
        $this->auditLogger->log('vault', 'config.password_changed', 'VaultUserConfig', $config->getId(), $this->auditPayload($config));
    }

    public function destroyVault(CoreUserInterface $user, VaultUserConfigInterface $config): void
    {
        $configId = $config->getId();
        $userId = $user->getId();

        $this->entityManager->createQuery('DELETE FROM Aurora\Module\Vault\VaultEntry\Entity\VaultEntry e WHERE e.user = :user')
            ->setParameter('user', $user)
            ->execute();

        $this->entityManager->createQuery('DELETE FROM Aurora\Module\Vault\VaultFolder\Entity\VaultFolder f WHERE f.user = :user')
            ->setParameter('user', $user)
            ->execute();

        $this->entityManager->remove($config);
        $this->entityManager->flush();

        $this->auditLogger->log('vault', 'config.destroyed', 'VaultUserConfig', $configId, ['userId' => $userId]);
    }

    protected function auditSetup(VaultUserConfigInterface $config): void
    {
        $this->auditLogger->log('vault', 'config.setup', 'VaultUserConfig', $config->getId(), $this->auditPayload($config));
    }

    protected function auditPayload(VaultUserConfigInterface $config): array
    {
        return ['userId' => $config->getUser()->getId()];
    }
}
