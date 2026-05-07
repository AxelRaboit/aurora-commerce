<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Entity;

use Aurora\Core\Auth\Enum\AccessRequestStatusEnum;
use Aurora\Core\Auth\Repository\AccessRequestRepository;
use Aurora\Core\Trait\TimestampableTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AccessRequestRepository::class)]
#[ORM\Table(name: 'core_access_requests')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'IDX_access_request_token', columns: ['token'])]
#[ORM\Index(name: 'IDX_access_request_status', columns: ['status'])]
class AccessRequest
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_access_request_id', allocationSize: 1)]
    #[ORM\Column]
    #[Groups(['access_request:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    #[Groups(['access_request:read'])]
    private ?string $reference = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['access_request:read'])]
    private ?string $requesterName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['access_request:read'])]
    private ?string $message = null;

    #[ORM\Column(length: 20, enumType: AccessRequestStatusEnum::class, options: ['default' => 'pending'])]
    #[Groups(['access_request:read'])]
    private AccessRequestStatusEnum $status = AccessRequestStatusEnum::Pending;

    public function __construct(
        #[ORM\Column(length: 255)]
        #[Groups(['access_request:read'])]
        private string $requesterEmail,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        #[Groups(['access_request:read'])]
        private DateTimeImmutable $expiresAt,
    ) {
        $this->token = bin2hex(random_bytes(32));
    }

    public function getId(): ?int
    {
        return $this->id;
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
