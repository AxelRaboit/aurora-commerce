<?php

declare(strict_types=1);

namespace Aurora\Module\Dev\Audit\Service;

use Aurora\Module\Dev\Audit\Entity\AuditLog;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\Platform\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

readonly class AuditLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SequenceGenerator $sequenceGenerator,
        private SettingRepository $settingRepository,
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

        $prefix = $this->settingRepository->get(ApplicationParameterEnum::CoreAuditLogPrefix->value, SequencePrefixEnum::AuditLog->value) ?? SequencePrefixEnum::AuditLog->value;
        $log->setReference($this->sequenceGenerator->next($prefix));

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
