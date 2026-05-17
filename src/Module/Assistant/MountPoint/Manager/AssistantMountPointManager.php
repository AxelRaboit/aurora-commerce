<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\MountPoint\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Assistant\MountPoint\Dto\AssistantMountPointInputInterface;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPoint;
use Aurora\Module\Assistant\MountPoint\Entity\AssistantMountPointInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AssistantMountPointManagerInterface::class)]
class AssistantMountPointManager implements AssistantMountPointManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(CoreUserInterface $user, AssistantMountPointInputInterface $input): AssistantMountPointInterface
    {
        $mountPoint = $this->createMountPoint();
        $mountPoint->setUser($user);
        $this->applyInput($mountPoint, $input);

        $this->entityManager->persist($mountPoint);
        $this->entityManager->flush();

        $this->auditCreated($mountPoint);

        return $mountPoint;
    }

    public function update(AssistantMountPointInterface $mountPoint, AssistantMountPointInputInterface $input): void
    {
        $this->applyInput($mountPoint, $input);
        $this->entityManager->flush();

        $this->auditUpdated($mountPoint);
    }

    public function delete(AssistantMountPointInterface $mountPoint): void
    {
        $this->auditDeleted($mountPoint);

        $this->entityManager->remove($mountPoint);
        $this->entityManager->flush();
    }

    protected function applyInput(AssistantMountPointInterface $mountPoint, AssistantMountPointInputInterface $input): void
    {
        $mountPoint->setName($input->getName());
        $mountPoint->setPath($this->normalisePath($input->getPath()));
        $mountPoint->setAccess($input->getAccess());
        $mountPoint->setActive($input->isActive());
    }

    /** Trim trailing slash so prefix-match comparisons in the tool layer behave. */
    protected function normalisePath(string $path): string
    {
        $trimmed = mb_rtrim($path, '/');

        return '' === $trimmed ? '/' : $trimmed;
    }

    protected function createMountPoint(): AssistantMountPointInterface
    {
        return new AssistantMountPoint();
    }

    protected function auditCreated(AssistantMountPointInterface $mountPoint): void
    {
        $this->auditLogger->log('assistant', 'mount_point.created', 'AssistantMountPoint', $mountPoint->getId(), $this->auditPayload($mountPoint));
    }

    protected function auditUpdated(AssistantMountPointInterface $mountPoint): void
    {
        $this->auditLogger->log('assistant', 'mount_point.updated', 'AssistantMountPoint', $mountPoint->getId(), $this->auditPayload($mountPoint));
    }

    protected function auditDeleted(AssistantMountPointInterface $mountPoint): void
    {
        $this->auditLogger->log('assistant', 'mount_point.deleted', 'AssistantMountPoint', $mountPoint->getId(), $this->auditPayload($mountPoint));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(AssistantMountPointInterface $mountPoint): array
    {
        return [
            'name' => $mountPoint->getName(),
            'path' => $mountPoint->getPath(),
            'access' => $mountPoint->getAccess()->value,
            'active' => $mountPoint->isActive(),
        ];
    }
}
