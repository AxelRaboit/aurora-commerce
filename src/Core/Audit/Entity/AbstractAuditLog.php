<?php

declare(strict_types=1);

namespace Aurora\Core\Audit\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractAuditLog implements AuditLogInterface
{
    #[ORM\Column(length: 64, unique: true, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\Column(length: 30)]
        protected string $module,
        #[ORM\Column(length: 100)]
        protected string $action,
        #[ORM\Column(length: 100, nullable: true)]
        protected ?string $entityType = null,
        #[ORM\Column(nullable: true)]
        protected ?int $entityId = null,
        #[ORM\Column(nullable: true)]
        protected ?int $userId = null,
        #[ORM\Column(length: 180, nullable: true)]
        protected ?string $userEmail = null,
        #[ORM\Column(length: 180, nullable: true)]
        protected ?string $userName = null,
        #[ORM\Column(type: Types::JSON, nullable: true)]
        protected ?array $data = null,
    ) {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
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
