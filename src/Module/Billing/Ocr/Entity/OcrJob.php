<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Ocr\Entity;

use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Billing\Ocr\Enum\OcrJobStatusEnum;
use Aurora\Module\Billing\Ocr\Repository\OcrJobRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OcrJobRepository::class)]
#[ORM\Table(name: 'billing_ocr_jobs')]
#[ORM\Index(name: 'idx_billing_ocr_status', columns: ['status'])]
#[ORM\HasLifecycleCallbacks]
class OcrJob
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Media $media;

    #[ORM\Column(length: 32, enumType: OcrJobStatusEnum::class, options: ['default' => 'queued'])]
    private OcrJobStatusEnum $status = OcrJobStatusEnum::Queued;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $modelUsed = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rawDoctr = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rawVlm = null;

    /** Final structured payload (matches InvoiceDraftDto::toArray()). */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $extracted = null;

    #[ORM\Column(nullable: true)]
    private ?float $confidence = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error = null;

    /** @var list<array{level: string, message: string, context: array<string,mixed>, ts: string}> */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $logs = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMedia(): Media
    {
        return $this->media;
    }

    public function setMedia(Media $media): self
    {
        $this->media = $media;

        return $this;
    }

    public function getStatus(): OcrJobStatusEnum
    {
        return $this->status;
    }

    public function setStatus(OcrJobStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getModelUsed(): ?string
    {
        return $this->modelUsed;
    }

    public function setModelUsed(?string $modelUsed): self
    {
        $this->modelUsed = $modelUsed;

        return $this;
    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?DateTimeImmutable $finishedAt): self
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function getRawDoctr(): ?array
    {
        return $this->rawDoctr;
    }

    public function setRawDoctr(?array $rawDoctr): self
    {
        $this->rawDoctr = $rawDoctr;

        return $this;
    }

    public function getRawVlm(): ?array
    {
        return $this->rawVlm;
    }

    public function setRawVlm(?array $rawVlm): self
    {
        $this->rawVlm = $rawVlm;

        return $this;
    }

    public function getExtracted(): ?array
    {
        return $this->extracted;
    }

    public function setExtracted(?array $extracted): self
    {
        $this->extracted = $extracted;

        return $this;
    }

    public function getConfidence(): ?float
    {
        return $this->confidence;
    }

    public function setConfidence(?float $confidence): self
    {
        $this->confidence = $confidence;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): self
    {
        $this->error = $error;

        return $this;
    }

    /** @return list<array{level: string, message: string, context: array<string,mixed>, ts: string}> */
    public function getLogs(): array
    {
        return $this->logs ?? [];
    }

    public function appendLog(string $level, string $message, array $context = []): self
    {
        // Assign a new array so Doctrine's change-tracking detects the mutation.
        $this->logs = [...($this->logs ?? []), [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'ts' => new DateTimeImmutable()->format(DateTimeInterface::ATOM),
        ]];

        return $this;
    }
}
