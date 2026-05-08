<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Entity;

use Aurora\Core\Auth\Enum\AccessRequestStatusEnum;
use Aurora\Core\Trait\TimestampableTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractAccessRequest implements AccessRequestInterface
{
    use TimestampableTrait;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    #[Groups(['access_request:read'])]
    protected ?string $reference = null;

    #[ORM\Column(length: 64, unique: true)]
    protected string $token;

    #[ORM\Column(length: 255)]
    #[Groups(['access_request:read'])]
    protected string $requesterEmail;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['access_request:read'])]
    protected DateTimeImmutable $expiresAt;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['access_request:read'])]
    protected ?string $requesterName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['access_request:read'])]
    protected ?string $message = null;

    #[ORM\Column(length: 20, enumType: AccessRequestStatusEnum::class, options: ['default' => 'pending'])]
    #[Groups(['access_request:read'])]
    protected AccessRequestStatusEnum $status = AccessRequestStatusEnum::Pending;

    public function __construct(string $requesterEmail, DateTimeImmutable $expiresAt)
    {
        $this->requesterEmail = $requesterEmail;
        $this->expiresAt = $expiresAt;
        $this->token = bin2hex(random_bytes(32));
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

    public function getToken(): string
    {
        return $this->token;
    }

    public function getRequesterEmail(): string
    {
        return $this->requesterEmail;
    }

    public function getRequesterName(): ?string
    {
        return $this->requesterName;
    }

    public function setRequesterName(?string $requesterName): static
    {
        $this->requesterName = $requesterName;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getStatus(): AccessRequestStatusEnum
    {
        return $this->status;
    }

    public function setStatus(AccessRequestStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isPending(): bool
    {
        return AccessRequestStatusEnum::Pending === $this->status;
    }

    public function isApproved(): bool
    {
        return AccessRequestStatusEnum::Approved === $this->status;
    }

    public function isRejected(): bool
    {
        return AccessRequestStatusEnum::Rejected === $this->status;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }
}
