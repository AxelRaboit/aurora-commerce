<?php

declare(strict_types=1);

namespace Aurora\Core\Audit\Service;

use Aurora\Core\Audit\Entity\AuditLog;
use Aurora\Core\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

readonly class AuditLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {}

    public function log(
        string $module,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $data = null,
    ): void {
        $user = $this->security->getUser();
        $appUser = $user instanceof User ? $user : null;

        $log = new AuditLog(
            module: $module,
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            userId: $appUser?->getId(),
            userEmail: $user?->getUserIdentifier(),
            userName: $appUser?->getName(),
            data: $data,
        );

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
