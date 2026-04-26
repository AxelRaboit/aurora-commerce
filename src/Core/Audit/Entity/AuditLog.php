<?php

declare(strict_types=1);

namespace App\Core\Audit\Entity;

use App\Core\Audit\Repository\AuditLogRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_logs')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\Column(length: 30)]
        private string $module,
        #[ORM\Column(length: 100)]
        private string $action,
        #[ORM\Column(length: 100, nullable: true)]
        private ?string $entityType = null,
        #[ORM\Column(nullable: true)]
        private ?int $entityId = null,
        #[ORM\Column(nullable: true)]
        private ?int $userId = null,
        #[ORM\Column(length: 180, nullable: true)]
        private ?string $userEmail = null,
        #[ORM\Column(length: 180, nullable: true)]
        private ?string $userName = null,
        #[ORM\Column(type: Types::JSON, nullable: true)]
        private ?array $data = null,
    ) {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
