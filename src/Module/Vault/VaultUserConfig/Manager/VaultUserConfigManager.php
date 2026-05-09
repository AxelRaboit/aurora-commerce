<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultUserConfig\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\User\Entity\CoreUserInterface;
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

    protected function auditSetup(VaultUserConfigInterface $config): void
    {
        $this->auditLogger->log('vault', 'config.setup', 'VaultUserConfig', $config->getId(), $this->auditPayload($config));
    }

    protected function auditPayload(VaultUserConfigInterface $config): array
    {
        return ['userId' => $config->getUser()->getId()];
    }
}
