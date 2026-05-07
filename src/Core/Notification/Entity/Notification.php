<?php

declare(strict_types=1);

namespace Aurora\Core\Notification\Entity;

use Aurora\Core\Notification\Repository\NotificationRepository;
use Aurora\Core\Trait\TimestampableTrait;
use Aurora\Core\User\Entity\User;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'core_notifications')]
#[ORM\HasLifecycleCallbacks]
class Notification
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'seq_notification_id', allocationSize: 1)]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $recipient;

    /** Free-form type slug — e.g. 'project.task.assigned', 'project.task.mentioned'. */
    #[ORM\Column(length: 80)]
    private string $type;

    /** Human-readable title (already translated at write-time). */
    #[ORM\Column(length: 255)]
    private string $title;

    /** Optional body. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $body = null;

    /** Deep link to the related entity ('/backend/projects/12'). */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $url = null;

    /** Arbitrary JSON payload (entity ids, etc.). */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $readAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipient(): User
    {
        return $this->recipient;
    }

    public function setRecipient(User $user): static
    {
        $this->recipient = $user;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getReadAt(): ?DateTimeImmutable
    {
        return $this->readAt;
    }

    public function markAsRead(): static
    {
        $this->readAt = new DateTimeImmutable();

        return $this;
    }
}
