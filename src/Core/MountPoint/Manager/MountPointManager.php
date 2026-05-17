<?php

declare(strict_types=1);

namespace Aurora\Core\MountPoint\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\MountPoint\Dto\MountPointInputInterface;
use Aurora\Core\MountPoint\Entity\MountPoint;
use Aurora\Core\MountPoint\Entity\MountPointInterface;
use Aurora\Core\MountPoint\Service\MountPointEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MountPointManagerInterface::class)]
class MountPointManager implements MountPointManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly MountPointEncryptionService $encryptionService,
    ) {}

    public function create(MountPointInputInterface $input): MountPointInterface
    {
        $mountPoint = $this->createMountPoint();
        $this->applyInput($mountPoint, $input);

        $this->entityManager->persist($mountPoint);
        $this->entityManager->flush();

        $this->auditCreated($mountPoint);

        return $mountPoint;
    }

    public function update(MountPointInterface $mountPoint, MountPointInputInterface $input): void
    {
        $this->applyInput($mountPoint, $input);
        $this->entityManager->flush();

        $this->auditUpdated($mountPoint);
    }

    public function delete(MountPointInterface $mountPoint): void
    {
        $this->auditDeleted($mountPoint);

        $this->entityManager->remove($mountPoint);
        $this->entityManager->flush();
    }

    protected function createMountPoint(): MountPointInterface
    {
        return new MountPoint();
    }

    protected function applyInput(MountPointInterface $mountPoint, MountPointInputInterface $input): void
    {
        $mountPoint->setName($input->getName());
        $mountPoint->setType($input->getType());
        $mountPoint->setHost($input->getHost());
        $mountPoint->setPort($input->getPort());
        $mountPoint->setUsername($input->getUsername());
        $mountPoint->setDatabase($input->getDatabase());
        $mountPoint->setSshPublicKey($input->getSshPublicKey());
        $mountPoint->setConfig($input->getConfig());

        // Encrypt secrets only when a new value is provided; keep existing if null.
        if (null !== $input->getPassword()) {
            $mountPoint->setPassword($this->encryptionService->encrypt($input->getPassword()));
        }

        if (null !== $input->getSshPrivateKey()) {
            $mountPoint->setSshPrivateKey($this->encryptionService->encrypt($input->getSshPrivateKey()));
        }
    }

    protected function auditCreated(MountPointInterface $mountPoint): void
    {
        $this->auditLogger->log('core', 'mount_point.created', 'MountPoint', $mountPoint->getId(), $this->auditPayload($mountPoint));
    }

    protected function auditUpdated(MountPointInterface $mountPoint): void
    {
        $this->auditLogger->log('core', 'mount_point.updated', 'MountPoint', $mountPoint->getId(), $this->auditPayload($mountPoint));
    }

    protected function auditDeleted(MountPointInterface $mountPoint): void
    {
        $this->auditLogger->log('core', 'mount_point.deleted', 'MountPoint', $mountPoint->getId(), $this->auditPayload($mountPoint));
    }

    /**
     * Returns the structured payload logged by every audit entry. Override in
     * a subclass to add extra fields: `[...parent::auditPayload($mountPoint), 'host' => $mountPoint->getHost()]`.
     */
    protected function auditPayload(MountPointInterface $mountPoint): array
    {
        return ['name' => $mountPoint->getName(), 'type' => $mountPoint->getType()->value];
    }
}
