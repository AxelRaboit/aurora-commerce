<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\Document\Entity;

use Aurora\Core\Media\Library\Entity\MediaInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractDocumentVersion implements DocumentVersionInterface
{
    #[ORM\ManyToOne(targetEntity: DocumentInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected DocumentInterface $document;

    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected MediaInterface $file;

    #[ORM\Column]
    protected int $versionNumber;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected DateTimeImmutable $createdAt;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $note = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getDocument(): DocumentInterface
    {
        return $this->document;
    }

    public function setDocument(DocumentInterface $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getFile(): MediaInterface
    {
        return $this->file;
    }

    public function setFile(MediaInterface $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
    }

    public function setVersionNumber(int $versionNumber): static
    {
        $this->versionNumber = $versionNumber;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }
}
